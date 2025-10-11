<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Controller\LendingController;
use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Model\LendingObject;
use Cylancer\CyLending\Domain\Model\ValidationResults;
use Cylancer\CyLending\Domain\Repository\LendingRepository;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

class ValidationService implements SingletonInterface
{

    public function __construct(
        private readonly FrontendUserService $frontendUserService,
        private readonly LendingRepository $lendingRepository,
        private readonly LendingService $lendingService,
    ) {
    }


    public function validate(
        Lending $lending,
        int $ceUid,
        string $reasonsForPreventionService,
        string $reasonsForPreventionServiceTitle
    ): ValidationResults {
        /** @var ValidationResults $validationResults */
        $validationResults = $lending->getValidationResults();
        if ($lending->getObject() == null) {
            $validationResults->addError('object.isEmpty');
        }

        if ($lending->getHighPriority()) {
            if ($lending->getObject()->getHighPriorityGroup() == null) {
                $validationResults->addError('highPriorityNotPossible');

            } else if (!$this->frontendUserService->containsUser($lending->getObject()->getHighPriorityGroup(), $lending->getBorrower())) {
                $validationResults->addError('highPriorityNotPossible');
            }
        }

        $from = false;
        if ($lending->getFrom() == null || str_starts_with($lending->getFrom(), '1970-01-01')) {
            $validationResults->addError('from.isEmpty');
        } else {
            $from = strtotime($lending->getFrom());
            if ($from === false) {
                $validationResults->addError('from.invalid');
            } else if ($from < time()) {
                $validationResults->addError('from.isInThePast');
            }
        }


        $until = false;
        if ($lending->getUntil() == null || str_starts_with($lending->getUntil(), '1970-01-01')) {
            $validationResults->addError('until.isEmpty');
        } else {
            $until = strtotime($lending->getUntil());
            if ($until === false) {
                $validationResults->addError('until.invalid');
            } else if ($until < time()) {
                $validationResults->addError('until.isInThePast');
            }
        }

        if ($from !== false && $until !== false && $from > $until) {
            $validationResults->addError('until.isBeforeFrom');
        } else {

            if (!empty($reasonsForPreventionService) && class_exists($reasonsForPreventionService)) {
                $storageUids = GeneralUtility::intExplode(',', $this->getSetting('reasonsForPreventionServiceStorageUids', $ceUid), TRUE);
                if (str_starts_with($reasonsForPreventionService, "\\")) {
                    $reasonsForPreventionService = substr($reasonsForPreventionService, 1);
                }
                $reasonsForPrevention = GeneralUtility::makeInstance($reasonsForPreventionService)::reasonsForPreventionAction
                (
                    $storageUids,
                    LendingRepository::string2DateTime($lending->getFrom()),
                    LendingRepository::string2DateTime($lending->getUntil())
                );
                /** @var  array */
                $data = $reasonsForPrevention['data'];
                if (count($data) > 0) {
                    $list = '';
                    if (!empty($reasonsForPreventionServiceTitle)) {
                        $list .= '<strong>' . $reasonsForPreventionServiceTitle . '</strong>';
                    }
                    $list .= '<ul>';
                    foreach ($data as $reason) {
                        $list .= '<li>' . $reason['description'] . '</li>';
                    }
                    $list .= '</ul>';
                    $validationResults->addWarning('from.reasonsForPrevention', [$list]);
                }
            }

            // individual objects can also be borrowed several times at the same time
            if ($lending->getObject()->getQuantity() == 1) {
                if (!$lending->getHighPriority() && $this->lendingRepository->existsOverlapsAvailabilityRequests($lending)) {
                    $validationResults->addWarning('existsOverlapsAvailabilityRequests');
                }

                // if the item is present more than once, the maximum number of pieces may not be exceeded
            } else {

                $max = $this->lendingService->calculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($lending));
                $available = $lending->getObject()->getQuantity() - $max;
                if ($available == 0) {
                    $validationResults->addError('quantity.notAvailable');
                } else if ($lending->getQuantity() > $available) {
                    $key = $available == 1 ? 'piece' : 'pieces';
                    $validationResults->addError('quantity.tooLarge', [
                        $available,
                        LocalizationUtility::translate($key, LendingController::EXTENSION_NAME)
                    ]);
                } else {
                    $max = $this->lendingService->calculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($lending, LendingRepository::NO_LIMIT, Lending::STATE_AVAILABILITY_REQUEST));
                    if ($max > 0) {
                        $key = $max == 1 ? 'piece' : 'pieces';
                        $validationResults->addInfo('quantity.availabilityRequests', [
                            $max,
                            LocalizationUtility::translate($key, LendingController::EXTENSION_NAME)
                        ]);
                    }

                }

            }
        }

        if (strlen(trim($lending->getPurpose())) === 0) {
            $validationResults->addError('purpose.isEmpty');
        }
        return $validationResults;
    }

    public function validateApprove(
        Lending $lending,
        int $ceUid,
        string $reasonsForPreventionService,
        string $reasonsForPreventionServiceTitle,
        $stored = true,

    ): ValidationResults {
        /** @var ValidationResults $validationResults */
        $validationResults = $this->validate(
            $lending,
            $ceUid,
            $reasonsForPreventionService,
            $reasonsForPreventionServiceTitle
        );
        if ($stored && $this->lendingRepository->findByUid($lending->getUid()) == null) {
            $validationResults->addError('requestDeleted');
        } else {
            if (!$this->canApproveLendingObject($lending->getObject())) {
                $validationResults->addError('userNotApprover.approve');
            }
            if (
                !$lending->getHighPriority()
                && $this->lendingRepository->existsOverlapsAvailabilityRequests($lending)
            ) {
                $validationResults->addWarning('existsOverlapsAvailabilityRequests');
            }

            if ($lending->getState() != Lending::STATE_AVAILABILITY_REQUEST) {
                $validationResults->addError('requestAlreadyBeenProcessed');
            }
        }
        return $validationResults;
    }


    public function validateCancelAvailabilityRequest(Lending $availabilityRequest): ValidationResults
    {
        /** @var ValidationResults $validationResults */
        $validationResults = $availabilityRequest->getValidationResults();
        if ($availabilityRequest->getBorrower()->getUid() != $this->frontendUserService->getCurrentUserUid()) {
            $validationResults->addError('notYourLending');
        }
        return $validationResults;
    }

    public function validateCancel(Lending $myLending): ValidationResults
    {
        /** @var ValidationResults $validationResults */
        $validationResults = $myLending->getValidationResults();
        if ($myLending->getBorrower()->getUid() != $this->frontendUserService->getCurrentUserUid()) {
            $validationResults->addError('notYourLending');
        }
        return $validationResults;
    }

    public function validateReject(Lending $lending): ValidationResults
    {
        /** @var ValidationResults $validationResults */
        $validationResults = new ValidationResults();
        if ($this->lendingRepository->findByUid($lending->getUid()) == null) {
            $validationResults->addError('requestDeleted');
        } else {
            if (!$this->canApproveLendingObject($lending->getObject())) {
                $validationResults->addError('userNotApprover.reject');
            }
            if ($lending->getState() != Lending::STATE_AVAILABILITY_REQUEST) {
                $validationResults->addError('requestAlreadyBeenProcessed');
            }
        }
        return $validationResults;
    }

    private function canApproveLendingObject(?LendingObject $lendingObject): bool
    {
        if ($lendingObject == null) {
            return false;
        }

        if ($lendingObject->getApproverGroup() == null) {
            return true;
        }
        $approverGroupUid = $lendingObject->getApproverGroup()->getUid();
        foreach ($this->frontendUserService->getCurrentUser()->getUsergroup() as $frontendUserGroup) {
            if ($this->frontendUserService->containsGroup($frontendUserGroup, $approverGroupUid)) {
                return true;
            }
        }
        return false;
    }

}