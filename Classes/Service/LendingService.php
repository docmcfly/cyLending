<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Model\LendingObject;
use Cylancer\CyLending\Domain\Model\ValidationResults;
use Cylancer\CyLending\Domain\Repository\LendingObjectRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 *
 * This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */

class LendingService implements SingletonInterface
{

    public function __construct(
        private readonly LendingRepository $lendingRepository,
        private readonly LendingObjectRepository $lendingObjectRepository,
        private readonly FrontendUserService $frontendUserService,
        private readonly ValidationService $validationService,
        private readonly PersistenceManager $persistenceManager,
        private readonly SendService $sendService,
    ) {
    }

    /**
     * @param int $year
     * @param int $month
     * @param int[] $storagePids
     * @return array
     */
    public function getVisualAvailabilityRequestsAsEventsOf(int $year, int $month, array $storagePids = NULL): array
    {
        if ($storagePids != null) {
            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storagePids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);
        }

        /** @var \DateTime $startDate */
        $startDate = LendingRepository::toDateTime($year, $month);
        $startDate = LendingRepository::addDays($startDate, 1 - intval($startDate->format("N")));

        /** @var \DateTime $endDate */
        $endDate = LendingRepository::toDateTime($year, $month);
        $endDate = LendingRepository::addMonths($endDate, 1);
        $endDate = LendingRepository::addDays($endDate, 7 - intval($endDate->format("N")));

        $events = [];
        /** @var Lending $lending*/
        foreach ($this->lendingRepository->findFromUntilAvailabilityRequests($startDate, $endDate) as $lending) {

            /** @var LendingObject $lendingObject*/
            $lendingObject = $lending->getObject();
            $event = [];
            $event['idx'] = $lending->getUid();
            $event['start'] = $lending->getFrom();
            $event['end'] = $lending->getUntil();
            $event['title'] = ($lending->getHighPriority() ? '❗' : '') . $lendingObject->getTitle();
            $description = $lending->getPurpose();
            if ($lendingObject->getQuantity() > 1) {
                $key = $lending->getQuantity() == 1 ? 'piece' : 'pieces';
                $description .= ' (' . $lending->getQuantity() . ' ' . LocalizationUtility::translate($key, 'cy_lending') . ')';
            }
            $event['description'] = $description;
            $event['responsible'] = $lending->getBorrower()->getFirstName() . ' ' . $lending->getBorrower()->getLastName();
            $event['backgroundColor'] = $lendingObject->getColor();
            $event['striped'] = $lending->getState() == Lending::STATE_AVAILABILITY_REQUEST;

            $events[] = $event;
        }
        return $events;
    }

    public function calculateMaximumFrom(Lending $lending): int
    {
        return $this->calculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($lending));
    }

    public function calculateMaximum(QueryResultInterface|array $lendings): int
    {

        $o = null;
        $switchMoments = [];
        /** @var Lending $lending */
        foreach ($lendings as $lending) {
            if ($o == null) {

            }
            $q = $lending->getQuantity();
            $f = $lending->getFrom();
            $u = $lending->getUntil();

            if (isset($switchMoments[$f])) {
                $switchMoments[$f] += $q;
            } else {
                $switchMoments[$f] = $q;
            }
            if (isset($switchMoments[$u])) {
                $switchMoments[$u] -= $q;
            } else {
                $switchMoments[$u] = 0 - $q;
            }
        }
        ksort($switchMoments);

        /** @var int $maximum */
        $maximum = 0;

        $qtyState = 0;
        foreach ($switchMoments as $qty) {
            $qtyState += $qty;
            $maximum = max($maximum, $qtyState);
            // debug($qtyState, 'qtyState');
            // debug($maximum, 'maximum');
        }

        return $maximum;

    }



    private function prepareLendingRepository(array $flexformSettings)
    {
        $allLendingStorageUids = array_merge(
            GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $flexformSettings['otherLendingStorageUids'], TRUE)
        );

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

    }


    private function prepareLendingObjectRepository(array $flexformSettings)
    {

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(
            ',',
            $flexformSettings['lendingObjectStorageUids'],
            TRUE
        ));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);
        $this->lendingObjectRepository->setDefaultOrderings([
            'group_name' => QueryInterface::ORDER_ASCENDING,
            'title' => QueryInterface::ORDER_ASCENDING
        ]);

    }




    public function reserve(
        Lending $toReserve,
        array $confirmedWarnings = [],
        int $ceUid,
        RequestInterface $request,
        string $language,
        array $flexformSettings,
    ) {


        $toReserve->setBorrower($this->frontendUserService->getCurrentUser());
        $toReserve->setFrom($this->toDBDateTime($toReserve->getFrom()));
        $toReserve->setUntil($this->toDBDateTime($toReserve->getUntil()));
        $toReserve->setPid(intval($flexformSettings['lendingStorageUids']));

        $this->prepareLendingObjectRepository($flexformSettings);
        $this->prepareLendingRepository($flexformSettings);

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateReserve(
            $toReserve,
            $confirmedWarnings,
            $flexformSettings,
        );
        $validationResults->setConfirmedWarnings($confirmedWarnings);

        if ($validationResults->isOkay() && $toReserve->getObject()->getApproverGroup() == null) {
            // simulate an availability request...
            $state = $toReserve->getState();
            $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST); // only for the validation
            $validationResults = $this->validationService->validateApprove(
                $toReserve,
                $confirmedWarnings,
                $flexformSettings,
            );
            $toReserve->setState($state); // reset the state

        }


        if ($validationResults->isOkay()) {
            $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST);
            $this->lendingRepository->add($toReserve);
            $this->persistenceManager->persistAll();

            if ($toReserve->getObject()->getApproverGroup() == null) {
                /** @var Lending $toReserve */
                $toReserve = $this->lendingRepository->findByUid($toReserve->getUid());
                $this->approve(
                    $toReserve,
                    $confirmedWarnings,
                    $ceUid,
                    $request,
                    $language,
                    $flexformSettings
                );
                if ($toReserve->getValidationResults()->hasErrors()) {
                    $validationResults->addError('automaticApproved');
                } else if ($toReserve->getValidationResults()->isOkay()) {
                    $validationResults->clear();
                    $validationResults->addInfo('automaticApproved');
                }
            } else {
                $validationResults->clear();
                $validationResults->addInfo('successful');
                $this->sendService->sendApproverEMails(
                    $toReserve,
                    $this->getPageId($request),
                    GeneralUtility::intExplode(',', $flexformSettings['frontendUserStroageUids']),
                    $request,
                    $language
                );
            }
        }
        $validationResults->setConfirmedWarnings($confirmedWarnings);
        $toReserve->setContext('toReserve');
        return $validationResults;
    }

    /**
     * @param Lending|int $availabilityRequest
     * @param string[] $confirmedWarnings
     * @param int $ceUid
     * @param RequestInterface $request
     * @param string $language
     * @return ValidationResults
     */
    public function approve(
        Lending $availabilityRequest,
        array $confirmedWarnings = [],
        int $ceUid,
        RequestInterface $request,
        string $language,
        array $flexformSettings,
    ): ValidationResults {

        /** @var ValidationResults $validationResults */

        $validationResults = $this->validationService->validateApprove(
            lending: $availabilityRequest,
            flexformSettings: $flexformSettings,
            confirmedWarnings: $confirmedWarnings,
        );

        if ($validationResults->isOkay()) {

            $availabilityRequest->setState(Lending::STATE_APPROVED);
            $availabilityRequest->setApprover($this->frontendUserService->getCurrentUser());

            $this->lendingRepository->update($availabilityRequest);

            $previousBorrowerRequests = [];

            if ($availabilityRequest->getHighPriority()) {
                foreach ($this->lendingRepository->getOverlapsAvailabilityRequests($availabilityRequest) as $req) {
                    if ($req->getState() == Lending::STATE_APPROVED) {
                        $req->setApprover($this->frontendUserService->getCurrentUser());
                        $req->setState(Lending::STATE_AVAILABILITY_REQUEST);
                        $this->lendingRepository->update($req);
                        $previousBorrowerRequests[] = $req;
                    }
                }
            }

            $this->persistenceManager->persistAll();
            $validationResults->clear();
            $validationResults->addInfo('successful.approved');


            foreach ($previousBorrowerRequests as $previousBorrowerRequest) {
                $this->sendService->informPreviousBorrower(
                    $previousBorrowerRequest,
                    $request,
                    $language
                );
            }

            $this->sendService->sendAvailabilityRequestResultMail(
                $availabilityRequest,
                $request,
                $language
            );
            $this->sendService->informObserverGroup(
                $availabilityRequest,
                GeneralUtility::intExplode(',', $flexformSettings['frontendUserStroageUids']),
                $request,
                $language
            );
        }
        $availabilityRequest->setValidationResults($validationResults);
        $validationResults->setConfirmedWarnings($confirmedWarnings);
        $availabilityRequest->setContext('approval');

        return $validationResults;

    }

    /**
     * Summary of reject
     * @param \Cylancer\CyLending\Domain\Model\Lending|int $availabilityRequest
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param string $language
     * @param array $flexformSettings
     * @return ValidationResults
     */
    public function reject(
        Lending|int $availabilityRequest,
        RequestInterface $request,
        string $language,
        array $flexformSettings,
    ): ValidationResults {

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateReject($availabilityRequest);

        if ($validationResults->isOkay()) {
            $availabilityRequest->setState(Lending::STATE_REJECTED);
            $availabilityRequest->setApprover($this->frontendUserService->getCurrentUser());

            $this->lendingRepository->update($availabilityRequest);
            $this->persistenceManager->persistAll();
            $validationResults->clear();
            $validationResults->addInfo('successful.rejected');

            $this->sendService->sendAvailabilityRequestResultMail(
                $availabilityRequest,
                $request,
                $language
            );
            $this->sendService->informObserverGroup(
                $availabilityRequest,
                GeneralUtility::intExplode(',', $flexformSettings['frontendUserStroageUids'], TRUE),
                $request,
                $language
            );
        }
        $availabilityRequest->setValidationResults($validationResults);
        $availabilityRequest->setContext('approval');
        return $validationResults;
    }

    /**
     * @param \Cylancer\CyLending\Domain\Model\Lending $availabilityRequest
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param string $language
     * @param int[] $frontendUserStroageUids
     * @param \Cylancer\CyLending\Domain\Model\Lending|null $myLending
     * @return \Cylancer\CyLending\Domain\Model\ValidationResults
     */
    public function cancelMyLendingAction(
        Lending $myLending,
        RequestInterface $request,
        string $language,
        array $frontendUserStroageUids
    ): ValidationResults {

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateCancel($myLending);
        if ($validationResults->isOkay()) {
            $validationResults->clear();
            $validationResults->addInfo('successful');

            $myLending->setState(Lending::STATE_CANCELED);
            $this->lendingRepository->update($myLending);
            $this->persistenceManager->persistAll();

            $this->sendService->informAboutCanceling(
                $myLending,
                $frontendUserStroageUids,
                $request,
                $language
            );
        }
        $myLending->setContext('cancelMyLending');

        return $validationResults;
    }


    /**
     * @param \Cylancer\CyLending\Domain\Model\Lending $availabilityRequest
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param string $language
     * @param int[] $frontendUserStroageUids
     * @param \Cylancer\CyLending\Domain\Model\Lending|null $myLending
     * @return \Cylancer\CyLending\Domain\Model\ValidationResults
     */
    public function cancelMyAvailabilityRequest(
        Lending $availabilityRequest,
        RequestInterface $request,
        string $language,
        array $frontendUserStroageUids
    ): ValidationResults {

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateCancelAvailabilityRequest($availabilityRequest);
        if ($validationResults->isOkay()) {
            $validationResults->clear();
            $validationResults->addInfo('successful');

            $availabilityRequest->setState(Lending::STATE_CANCELED);
            $this->lendingRepository->update($availabilityRequest);
            $this->persistenceManager->persistAll();

            $this->sendService->informAboutCanceling(
                $availabilityRequest,
                $frontendUserStroageUids,
                $request,
                $language
            );
        }
        $availabilityRequest->setContext('cancelMyAvailabilityRequest');

        return $validationResults;
    }

    private function toDBDateTime(string $dateTime): string
    {
        return date(LendingRepository::SQL_DATE_FORMAT, strtotime($dateTime));
    }


    private function getPageId(RequestInterface $request): int
    {
        return $request->getAttribute('currentContentObject')->data['pid'];
    }

}
