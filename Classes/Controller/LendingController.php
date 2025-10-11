<?php

namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Model\LendingObject;
use Cylancer\CyLending\Domain\Model\ValidationResults;
use Cylancer\CyLending\Domain\Repository\ContentElementRepository;
use Cylancer\CyLending\Domain\Repository\FrontendUserGroupRepository;
use Cylancer\CyLending\Domain\Repository\FrontendUserRepository;
use Cylancer\CyLending\Domain\Repository\LendingObjectRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\LendingService;
use Cylancer\CyLending\Service\MiscService;
use Cylancer\CyLending\Service\SendService;
use Cylancer\CyLending\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Cylancer\CyLending\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Cylancer\CyLending\Domain\Model\FrontendUserGroup;


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

class LendingController extends ActionController
{

    private const EXTENSION_NAME = 'cy_lending';
    private const APPROVER_MAIL_TEMPLATE = 'ApproverMessageMail';
    private const AVAILABILITY_REQUEST_RESULT_MAIL_TEMPLATE = 'AvailabilityRequestResultMail';
    private const INFORM_PREVIOUS_BORROWER_MAIL_TEMPLATE = 'InformPreviousBorrowerMail';
    private const OBSERVER_MAIL_TEMPLATE = 'ObserverMessageMail';
    private const INFORM_CANCEL_LENDING_MAIL_TEMPLATE = 'InformCancelLendingMail';
    private const INFORM_CANCEL_AVAILABILITY_REQUEST_MAIL_TEMPLATE = 'InformCancelAvailabilityRequestMail';
    private const TO_RESERVE = 'toReserve';
    private const APPROVE_REQUEST = 'approveRequest';
    private const REJECT_REQUEST = 'rejectRequest';
    private const CANCEL_LENDING = 'cancelLending';
    private const CANCEL_AVAILABILITY_REQUEST = 'cancelAvailabilityRequest';
    private const CONTENT_ELEMENT = 'contentElement';

    private const TAB_KEY = 'tab';
    private const LENDING_TAB = 'lending';
    private const CALENDAR_TAB = 'calendar';
    private const APPROVAL_TAB = 'approval';
    private const MY_LENDINGS_TAB = 'myLendings';
    private const MY_AVAILABILITY_REQUESTS_TAB = 'myAvailabilityRequests';

    public function __construct(
        private readonly Context $context,
        private readonly LendingRepository $lendingRepository,
        private readonly LendingObjectRepository $lendingObjectRepository,
        private readonly FrontendUserGroupRepository $frontendUserGroupRepository,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly ContentElementRepository $contentElementRepository,
        private readonly MiscService $miscService,
        private readonly FrontendUserService $frontendUserService,
        private readonly PersistenceManager $persistenceManager,
        private readonly LendingService $lendingService,
        private readonly SiteFinder $siteFinder,
        private readonly ValidationService $validationService,
        private readonly SendService $sendService,
    ) {
    }

    private function getAllCanApproveLendingObject(QueryResult $lendingObjects): array
    {
        $canApproveLendingObjects = [];
        /** @var \Cylancer\CyLending\Domain\Model\LendingObject $lendingObject */
        /** @var \Cylancer\CyLending\Domain\Model\FrontendUserGroup $frontendUserGroup */
        foreach ($lendingObjects as $lendingObject) {
            /** @var \Cylancer\CyLending\Domain\Model\FrontendUserGroup $approverGroup */
            $approverGroup = $lendingObject->getApproverGroup();
            if ($approverGroup != null) {
                $approverGroupUid = $approverGroup->getUid();
                foreach ($this->frontendUserService->getCurrentUser()->getUsergroup() as $frontendUserGroup) {
                    if ($this->frontendUserService->containsGroup($frontendUserGroup, $approverGroupUid)) {
                        $canApproveLendingObjects[$lendingObject->getUid()] = $lendingObject;
                    }
                }
            }
        }
        return $canApproveLendingObjects;
    }


    private function getSetting($key, ?int $uid = null): mixed
    {
        if ($uid != null && !isset($this->settings[$key])) {
            array_merge($this->settings, $this->miscService->getFlexformSettings($uid));
        }
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    private function getAllLendingStorageUids(?int $uid = null): array
    {
        return array_merge(
            GeneralUtility::intExplode(',', $this->getSetting('lendingStorageUids', $uid), TRUE),
            GeneralUtility::intExplode(',', $this->getSetting('otherLendingStorageUids', $uid), TRUE)
        );

    }

    private function getContentElementUid(): int
    {
        return $this->request->hasArgument(LendingController::CONTENT_ELEMENT)
            ? $this->request->getArgument(LendingController::CONTENT_ELEMENT)
            : $this->request->getAttribute('currentContentObject')->data['uid'];
    }

    public function showAction(): ResponseInterface
    {
        $ceUid = $this->getContentElementUid();
        // prepare
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $this->getSetting('lendingObjectStorageUids', $ceUid), TRUE));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);
        $this->lendingObjectRepository->setDefaultOrderings(['group_name' => QueryInterface::ORDER_ASCENDING, 'title' => QueryInterface::ORDER_ASCENDING]);

        $allLendingStorageUids = $this->getAllLendingStorageUids($ceUid);

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

        $lendingObjects = $this->lendingObjectRepository->findAll();

        $canApproveLendingObjects = $this->getAllCanApproveLendingObject($lendingObjects);


        /** @var Lending $toReserve */
        $toReserve = $this->request->hasArgument(LendingController::TO_RESERVE)
            ? $this->fromArray($this->request->getArgument(LendingController::TO_RESERVE))
            : $this->createLending();
        $toReserve->setAllowedObjects($lendingObjects);
        $toReserve->updateAllowedObjects($this->frontendUserService, $this->lendingService);

        $allAvailabilityRequests = $this->lendingRepository->findAllAvailabilityRequests($canApproveLendingObjects);
        if ($this->request->hasArgument(LendingController::APPROVE_REQUEST)) {
            $this->mergeLendingValidationResults($allAvailabilityRequests, LendingController::APPROVE_REQUEST);
        }

        if ($this->request->hasArgument(LendingController::REJECT_REQUEST)) {
            $this->mergeLendingValidationResults($allAvailabilityRequests, LendingController::REJECT_REQUEST);
        }

        $today = getdate();
        // active tab

        // COMMON
        $this->view->assign(LendingController::CONTENT_ELEMENT, $ceUid);
        $this->view->assign('appointmentSymbol', $this->getSetting('appointmentSymbol', $ceUid));

        // tab page APPROVE:
        $this->view->assign('availabilityRequests', $allAvailabilityRequests);
        // all availability request to approve / reject
        $isApprover = !empty($canApproveLendingObjects);
        $this->view->assign('isApprover', $isApprover);

        // is the approver tab visible?

        // tab page MY_AVAILABILITY_REQUESTS:
        $myAvailabilityRequests = $this->lendingRepository->findMyAvailabilityRequests($this->frontendUserService->getCurrentUser());
        if ($this->request->hasArgument(LendingController::CANCEL_AVAILABILITY_REQUEST)) {
            $this->mergeLendingValidationResults($myAvailabilityRequests, LendingController::CANCEL_AVAILABILITY_REQUEST);
        }

        // tab page MY_LENDINGS:
        $myLendings = $this->lendingRepository->findMyLendings($this->frontendUserService->getCurrentUser());
        if ($this->request->hasArgument(LendingController::CANCEL_LENDING)) {
            $this->mergeLendingValidationResults($myLendings, LendingController::CANCEL_LENDING);
        }

        // all availability requests of the current user
        $this->view->assign('myAvailabilityRequests', $myAvailabilityRequests);

        // all lending of the current user
        $this->view->assign('myLendings', $myLendings);

        // tab page AVAILABILITY_REQUEST:
        $this->view->assign('lendingObjects', $lendingObjects);

        // validation results
        $this->view->assign('purposes', empty(trim($this->settings['purposes'])) ? [] : explode('\n', $this->settings['purposes']));

        // form drafts of purposes
        $this->view->assign('toReserve', $toReserve);

        // form object
        $this->view->assign('untilOffset', "var untilOffset = '" . intval($this->settings['usualLendingTermHours']) . ':' . intval($this->settings['usualLendingTermMinutes']) . "';\n");

        // for java script calculation of the until timestamp

        // tab page CALENDAR:
        $this->view->assign('allLendingStorageUids', $allLendingStorageUids);

        $this->view->assign('language', $this->getLanguage());
        // language for the calendar.js API
        $this->view->assign(
            'currentMonthEvents',
            json_encode(
                // initilial data of the current month
                $this->lendingService->getVisualAvailabilityRequestsAsEventsOf(
                    $today['year'],
                    $today['mon'],
                    $allLendingStorageUids
                )
            )
        );

        $queryTab = null;

        // COMMON
        if (isset($this->request->getQueryParams()['tab'])) {
            $allowedTabs = [LendingController::CALENDAR_TAB, LendingController::LENDING_TAB];
            if (count($myLendings) > 0) {
                $allowedTabs[] = LendingController::MY_LENDINGS_TAB;
            }
            if ($isApprover) {
                $allowedTabs[] = LendingController::APPROVAL_TAB;
            }
            if (count($myAvailabilityRequests) > 0) {
                $allowedTabs[] = LendingController::MY_AVAILABILITY_REQUESTS_TAB;
            }
            $queryTab = $this->request->getQueryParams()['tab'];
            if (!in_array($queryTab, $allowedTabs)) {
                $queryTab = null;
            }
        }

        $tab = $this->request->hasArgument(LendingController::TAB_KEY)
            ? $this->request->getArgument(LendingController::TAB_KEY)
            : (($queryTab != null) ? $queryTab : LendingController::CALENDAR_TAB);

        if (($myLendings == null || count($myLendings) == 0) && $tab === LendingController::MY_LENDINGS_TAB) {
            $tab = LendingController::CALENDAR_TAB;
        }
        $this->view->assign(LendingController::TAB_KEY, $tab);

        return $this->htmlResponse();
    }

    private function mergeLendingValidationResults(array &$lendings, string $context): void
    {
        $availabilityRequest = $this->fromArray($this->request->getArgument($context));
        $found = false;
        foreach ($lendings as $lending) {
            if ($lending->getUid() === $availabilityRequest->getUid()) {
                $lending->setValidationResults($availabilityRequest->getValidationResults());
                $lending->setContext($context);
                $found = true;
            }
        }
        if (!$found) {
            $tmp = $this->createLending();
            $tmp->setValidationResults($availabilityRequest->getValidationResults());
            $tmp->setContext($context);
            array_unshift($lendings, $tmp);
        }
    }

    private function createLending(): Lending
    {
        $return = new Lending();
        $now = time();
        $tomorrow = ((floor($now / (24 * 3600))) + 2) * (24 * 3600) + (12 * 3600);
        $return->setFrom(date('Y-m-d\TH:i', $tomorrow));
        $return->setValidationResults(new ValidationResults());
        $return->setBorrower($this->frontendUserService->getCurrentUser());

        return $return;
    }

    private function toDBDateTime(string $dateTime): string
    {
        return date(LendingRepository::SQL_DATE_FORMAT, strtotime($dateTime));
    }

    public function cancelAvailabilityRequestAction(Lending $myAvailabilityRequest = null): ResponseInterface
    {
        $ceUid = $this->getContentElementUid();

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateCancelAvailabilityRequest($myAvailabilityRequest);
        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        if ($validationResults->isOkay()) {
            $validationResults->clear();
            $validationResults->addInfo('successful');
            $myAvailabilityRequest->setState(Lending::STATE_CANCELED);
            $this->lendingRepository->update($myAvailabilityRequest);
            $this->persistenceManager->persistAll();

            $this->sendService->informAboutCancelAvailabilityRequest(
                $myAvailabilityRequest,
                GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']),
                $this->request,
                $this->getLanguage()
            );
        }

        $forwardArguments = [
            LendingController::TAB_KEY => LendingController::MY_AVAILABILITY_REQUESTS_TAB,
            LendingController::CONTENT_ELEMENT => $ceUid,
            LendingController::CANCEL_AVAILABILITY_REQUEST => $this->toArray($myAvailabilityRequest),

        ];

        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }

    public function cancelAction(Lending $myLending = null): ResponseInterface
    {
        $ceUid = $this->getContentElementUid();

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateCancel($myLending);
        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        if ($validationResults->isOkay()) {
            $validationResults->clear();
            $validationResults->addInfo('successful');

            $myLending->setState(Lending::STATE_CANCELED);
            $this->lendingRepository->update($myLending);
            $this->persistenceManager->persistAll();

            $this->sendService->informAboutCanceling(
                $myLending,
                GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']),
                $this->request,
                $this->getLanguage()
            );
        }

        $forwardArguments = [
            LendingController::TAB_KEY => LendingController::MY_LENDINGS_TAB,
            LendingController::CONTENT_ELEMENT => $ceUid,
            LendingController::CANCEL_LENDING => $this->toArray($myLending),

        ];

        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }

    public function reserveAction(Lending $toReserve = null): ResponseInterface
    {

        $ceUid = $this->getContentElementUid();

        // prepare
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $this->settings['lendingObjectStorageUids'], TRUE));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);

        $allLendingStorageUids = $this->getAllLendingStorageUids();

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

        $toReserve->setBorrower($this->frontendUserService->getCurrentUser());
        $toReserve->setFrom($this->toDBDateTime($toReserve->getFrom()));
        $toReserve->setUntil($this->toDBDateTime($toReserve->getUntil()));
        $toReserve->setPid(intval($this->settings['lendingStorageUids']));


        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validate(
            $toReserve,
            $ceUid,
            $this->getSetting('reasonsForPreventionService'),
            $this->getSetting('reasonsForPreventionServiceTitle')
        );
        if ($validationResults->isOkay() && $toReserve->getObject()->getApproverGroup() == null) {
            // simulate an availability request...
            $state = $toReserve->getState();
            $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST); // only for the validation
            $this->validationService->validateApprove(
                $toReserve,
                $ceUid,
                $this->getSetting('reasonsForPreventionService'),
                $this->getSetting('reasonsForPreventionServiceTitle'),
                false
            );
            $toReserve->setState($state); // reset the state

        }

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];

        if ($validationResults->isOkay()) {
            $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST);
            $this->lendingRepository->add($toReserve);
            $this->persistenceManager->persistAll();

            if ($toReserve->getObject()->getApproverGroup() == null) {
                $toReserve = $this->lendingRepository->findByUid($toReserve->getUid());
                /** @var ForwardResponse $forward */
                $this->approveAction($toReserve);
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
                    $this->getPageId(),
                    GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']),
                    $this->request,
                    $this->getLanguage()
                );
            }
        }

        $forwardArguments[LendingController::TAB_KEY] = LendingController::LENDING_TAB;
        $forwardArguments[LendingController::CONTENT_ELEMENT] = $ceUid;
        $forwardArguments[LendingController::TO_RESERVE] = $this->toArray($toReserve);
        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }

    private function getPageId(): int
    {
        return $this->request->getAttribute('currentContentObject')->data['pid'];
    }

    private function getLanguage(): string
    {
        return $this->siteFinder
            ->getSiteByPageId($this->getPageId())
            ->getLanguageById(
                $this->context->getPropertyFromAspect('language', 'id')
            )
            ->getLocale()
            ->getName();
    }



    public function rejectAction(Lending $availabilityRequest = null): ResponseInterface
    {

        $ceUid = $this->getContentElementUid();

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validationService->validateReject($availabilityRequest);

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];
        if ($validationResults->isOkay()) {
            $availabilityRequest->setState(Lending::STATE_REJECTED);
            $availabilityRequest->setApprover($this->frontendUserService->getCurrentUser());

            $this->lendingRepository->update($availabilityRequest);
            $this->persistenceManager->persistAll();
            $validationResults->clear();
            $validationResults->addInfo('successful.rejected');
            $forward = $forward->withArguments([
                LendingController::TAB_KEY => LendingController::APPROVAL_TAB,

            ]);
            $this->sendService->sendAvailabilityRequestResultMail(
                $availabilityRequest,
                $this->request,
                $this->getLanguage()
            );
            $this->sendService->informObserverGroup(
                $availabilityRequest,
                GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']),
                $this->request,
                $this->getLanguage()
            );
        }

        $availabilityRequest->setValidationResults($validationResults);

        $forwardArguments[LendingController::TAB_KEY] = LendingController::APPROVAL_TAB;
        $forwardArguments[LendingController::CONTENT_ELEMENT] = $ceUid;
        $forwardArguments[LendingController::REJECT_REQUEST] = $this->toArray($availabilityRequest);

        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }



    public function approveAction(Lending $availabilityRequest = null): ForwardResponse
    {
        $ceUid = $this->getContentElementUid();

        $allLendingStorageUids = array_merge(
            GeneralUtility::intExplode(',', $this->settings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $this->settings['otherLendingStorageUids'], TRUE)
        );

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];

        $this->lendingService->approve(
            $availabilityRequest == null && $this->request->hasArgument('availabilityRequest')
            ? intval($this->request->getArgument(argumentName: 'availabilityRequest'))
            : $availabilityRequest,
            $this->getContentElementUid()
        );


        if ($availabilityRequest == null && $this->request->hasArgument('availabilityRequest')) {
            $uid = intval($this->request->getArgument(argumentName: 'availabilityRequest'));
            $availabilityRequest = $this->lendingRepository->findByUid($uid);
        }


        /** @var ValidationResults $validationResults */

        $validationResults = $this->validationService->validateApprove(
            $availabilityRequest,
            $ceUid,
            $this->getSetting('reasonsForPreventionService'),
            $this->getSetting('reasonsForPreventionServiceTitle')
        );

        if ($validationResults->isOkay()) {

            $availabilityRequest->setState(Lending::STATE_APPROVED);
            $availabilityRequest->setApprover($this->frontendUserService->getCurrentUser());

            $this->lendingRepository->update($availabilityRequest);

            if ($availabilityRequest->getHighPriority()) {
                foreach ($this->lendingRepository->getOverlapsAvailabilityRequests($availabilityRequest) as $req) {
                    if ($req->getState() == Lending::STATE_APPROVED) {
                        $req->setApprover($this->frontendUserService->getCurrentUser());
                        $req->setState(Lending::STATE_AVAILABILITY_REQUEST);
                        $this->lendingRepository->update($req);
                        $this->sendService->informPreviousBorrower(
                            $req,
                            $this->request,
                            $this->getLanguage()
                        );
                    }
                }
            }

            $this->persistenceManager->persistAll();
            $validationResults->clear();
            $validationResults->addInfo('successful.approved');

            $this->sendService->sendAvailabilityRequestResultMail(
                $availabilityRequest,
                $this->request,
                $this->getLanguage()
            );
            $this->sendService->informObserverGroup(
                $availabilityRequest,
                GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']),
                $this->request,
                $this->getLanguage()
            );
        } else {
            $forwardArguments[LendingController::TAB_KEY] = LendingController::APPROVAL_TAB;
        }

        $forwardArguments[LendingController::TAB_KEY] = LendingController::APPROVAL_TAB;
        $forwardArguments[LendingController::CONTENT_ELEMENT] = $ceUid;
        $forwardArguments[LendingController::APPROVE_REQUEST] = $this->toArray($availabilityRequest);
        $forward = $forward->withArguments($forwardArguments);

        return $forward;
    }




    public function toArray(Lending $lending): array
    {
        return [
            'uid' => $lending->getUid(),
            'borrower' => $lending->getBorrower() != null ? $lending->getBorrower()->getUid() : null,
            'approver' => $lending->getApprover() != null ? $lending->getApprover()->getUid() : null,
            'object' => $lending->getObject() != null ? $lending->getObject()->getUid() : null,
            'validationResults' => $lending->getValidationResults(),
            'from' => $lending->getFrom(),
            'until' => $lending->getUntil(),
            'purpose' => $lending->getPurpose(),
            'quantity' => $lending->getQuantity(),
            'state' => $lending->getState(),
            'highPriority' => $lending->getHighPriority(),
        ];
    }

    public function fromArray($array): Lending
    {
        $return = isset($array['uid']) ? $this->lendingRepository->findByUid($array['uid']) : $this->createLending();
        $return->setBorrower(isset($array['borrower']) ? $this->frontendUserRepository->findByUid($array['borrower']) : null);
        $return->setApprover(isset($array['approver']) ? $this->frontendUserRepository->findByUid($array['approver']) : null);
        $return->setObject(isset($array['object']) ? $this->lendingObjectRepository->findByUid($array['object']) : null);
        $return->setFrom($array['from']);
        $return->setUntil($array['until']);
        $return->setPurpose($array['purpose']);
        $return->setQuantity($array['quantity']);
        $return->setState($array['state']);
        $return->setHighPriority($array['highPriority']);
        $return->setValidationResults($array['validationResults']);
        return $return;
    }

}
