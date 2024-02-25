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
use Cylancer\CyLending\Service\EmailSendService;
use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\LendingService;
use Cylancer\CyLending\Service\MiscService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Cylancer\CyLending\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Cylancer\CyLending\Domain\Model\FrontendUserGroup;

/**
 *
 * This file is part of the 'lending' Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2024 Clemens Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Controller
 */

class LendingController extends ActionController
{

    const EXTENSION_NAME = 'CyLending';

    const TX_EXTENSION_NAME = 'tx_cy_lending';

    const APPROVER_MAIL_TEMPLATE = 'ApproverMessageMail';

    const AVAILABILITY_REQUEST_RESULT_MAIL_TEMPLATE = 'AvailabilityRequestResultMail';
    const INFORM_PREVIOUS_BORROWER_MAIL_TEMPLATE = 'InformPreviousBorrowerMail';
    const OBSERVER_MAIL_TEMPLATE = 'ObserverMessageMail';
    const INFORM_CANCEL_LENDING_MAIL_TEMPLATE = 'InformCancelLendingMail';

    const TO_RESERVE = 'toReserve';

    const APPROVE_REQUEST = 'approveRequest';
    const REJECT_REQUEST = 'rejectRequest';
    const CANCEL_LENDING = 'cancelLending';
    const CONTEXT_ELEMENT = 'contentElement';

    const TAB_KEY = 'tab';

    const APROVAL_TAB = 'approval';
    const LENDING_TAB = 'lending';
    const CALENDAR_TAB = 'calendar';
    const MY_LENDINGS_TAB = 'myLendings';

    const HIGH_PRIORITY_LENDING_AUTOMATIC_APPROVE = false;

    /* @var LendingObjectRepository */
    private LendingObjectRepository $lendingObjectRepository;

    /* @var LendingRepository */
    private LendingRepository $lendingRepository;

    /* @var FrontendUserService */
    private FrontendUserService $frontendUserService;

    /* @var FrontendUserGroupRepository */
    private FrontendUserGroupRepository $frontendUserGroupRepository;

    /* @var FrontendUserRepository */
    private FrontendUserRepository $frontendUserRepository;

    /* @var ContentElementRepository contentElementRepository */
    private ContentElementRepository $contentElementRepository;

    /* @var MiscService */
    private MiscService $miscService;

    /* @var EmailSendService */
    private EmailSendService $emailSendService;

    private LendingService $lendingService;

    /** @var PersistenceManager **/
    private $persistenceManager;

    public function __construct(
        LendingRepository $lendingRepository,
        LendingObjectRepository $lendingObjectRepository,
        FrontendUserGroupRepository $frontendUserGroupRepository,
        FrontendUserRepository $frontendUserRepository,
        ContentElementRepository $contentElementRepository,
        MiscService $miscService,
        FrontendUserService $frontendUserService,
        PersistenceManager $persistenceManager,
        EmailSendService $emailSendService,
        LendingService $lendingService
    ) {
        $this->lendingRepository = $lendingRepository;
        $this->lendingObjectRepository = $lendingObjectRepository;
        $this->frontendUserGroupRepository = $frontendUserGroupRepository;
        $this->frontendUserRepository = $frontendUserRepository;
        $this->contentElementRepository = $contentElementRepository;
        $this->miscService = $miscService;
        $this->frontendUserService = $frontendUserService;
        $this->persistenceManager = $persistenceManager;
        $this->emailSendService = $emailSendService;
        $this->lendingService = $lendingService;
    }

    /**
     * @return QueryResult
     */
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
                    if ($this->frontendUserService->contains($frontendUserGroup, $approverGroupUid)) {
                        $canApproveLendingObjects[$lendingObject->getUid()] = $lendingObject;
                    }
                }
            }
        }
        return $canApproveLendingObjects;
    }

    /**
     * @return QueryResult
     */
    private function updateAllHighPriorityLendingPossibles(QueryResult $lendingObjects): bool
    {
        $return = false;
        /** @var \Cylancer\CyLending\Domain\Model\LendingObject $lendingObject */
        /** @var \Cylancer\CyLending\Domain\Model\FrontendUserGroup $frontendUserGroup */
        foreach ($lendingObjects as $lendingObject) {
            $lendingObject->updateIsHighPriorityLendingPossible($this->frontendUserService);
            $return = $return || $lendingObject->getHighPriorityLendingPossible();
        }
        return $return;
    }

    private function getSetting($key, ?int $uid = null)
    {
        if ($uid != null && !isset($this->settings[$key])) {
            array_merge($this->settings, $this->miscService->getFlexformSettings($uid, $key));
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

    /**
     * @return ResponseInterface
     */
    public function showAction(): ResponseInterface
    {
        $ceUid = $this->request->hasArgument(LendingController::CONTEXT_ELEMENT) ? $this->request->getArgument(LendingController::CONTEXT_ELEMENT) : $this->configurationManager->getContentObject()->data['uid'];

        // prepare
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $this->getSetting('lendingObjectStorageUids', $ceUid), TRUE));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);

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

        $allAvailabilityRequests = $this->lendingRepository->findAllAvailabilityRequests($canApproveLendingObjects);
        if ($this->request->hasArgument(LendingController::APPROVE_REQUEST)) {
            $this->mergeLendingValidationResults($allAvailabilityRequests, LendingController::APPROVE_REQUEST);
        }

        if ($this->request->hasArgument(LendingController::REJECT_REQUEST)) {
            $this->mergeLendingValidationResults($allAvailabilityRequests, LendingController::REJECT_REQUEST);
        }

        $today = getdate();

        $tab = $this->request->hasArgument(LendingController::TAB_KEY)
            ? $this->request->getArgument(LendingController::TAB_KEY)
            : LendingController::CALENDAR_TAB;

        // active tab

        // COMMON
        $this->view->assign(LendingController::CONTEXT_ELEMENT, $ceUid);

        // tab page APPROVE:
        $this->view->assign('availabilityRequests', $allAvailabilityRequests);
        // all availability request to approve / reject
        $this->view->assign('isApprover', !empty($canApproveLendingObjects));
        $this->view->assign('canHighPriorityLending', $this->updateAllHighPriorityLendingPossibles($lendingObjects));
        // is the approver tab visible?

        // tab page MY_LENDINGS:
        $myLendings = $this->lendingRepository->findMyLendings($this->frontendUserService->getCurrentUser());
        if ($this->request->hasArgument(LendingController::CANCEL_LENDING)) {
            $this->mergeLendingValidationResults($myLendings, LendingController::CANCEL_LENDING);
        }

        // all availability request to approve / reject
        $this->view->assign('myLendings', $myLendings);

        // tab page AVAILABILITY_REQUEST:
        $this->updateAvailabilities($toReserve, $lendingObjects);

        $this->view->assign('lendingObjects', $lendingObjects);
        $this->view->assign('maxLendingQuantity', $this->getMaxQuantity($lendingObjects));

        // validation results
        $this->view->assign('purposes', empty(trim($this->settings['purposes'])) ? [] : explode('\n', $this->settings['purposes']));

        // form drafts of purposes
        $this->view->assign('toReserve', $toReserve);

        // form object
        $this->view->assign('untilOffset', "var untilOffset = '" . intval($this->settings['usualLendingTermHours']) . ':' . intval($this->settings['usualLendingTermMinutes']) . "';\n");

        // for java script calculation of the until timestamp

        // tab page CALENDAR:
        $this->view->assign('allLendingStorageUids', $allLendingStorageUids);
        $this->view->assign('language', $GLOBALS['TSFE']->language->getTypo3Language());
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

        // COMMON
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


    /**
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array $lendingObjects $name
     * @return int
     */
    private function getMaxQuantity($lendingObjects): int
    {
        $max = 0;
        foreach ($lendingObjects as $lendingObject) {
            $max = max($max, $lendingObject->getQuantity());
        }
        return $max;
    }



    /**
     * @param Lending $lending
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array $lendingObjects $name
     * @return ResponseInterface
     * @\TYPO3\CMS\Extbase\Annotation\Validate( param = "lending", validator = "Cylancer\CyLending\Domain\Validator\LendingValidator" )
     */
    private function updateAvailabilities(Lending $lending, $lendingObjects): void
    {
        /** @var Lending $tmp */
        $tmp = $this->createLending();
        $tmp->setFrom($lending->getFrom());
        $tmp->setUntil($lending->getUntil());
        foreach ($lendingObjects as $lendingObject) {
            $tmp->setObject($lendingObject);
            $max = $this->lendingService->caluculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($tmp));
            $lendingObject->setAvailableQuantity($lendingObject->getQuantity() - $max);
        }

    }

    private function createLending(): Lending
    {
        $return = new Lending();
        $now = time();
        $tomorrow = ((floor($now / (24 * 3600))) + 2) * (24 * 3600) + (12 * 3600);
        $return->setFrom(date('Y-m-d\TH:i', $tomorrow));
        $return->setValidationResults(new ValidationResults());
        return $return;
    }

    private function toDBDateTime(string $dateTime)
    {
        return date(LendingRepository::SQL_DATE_FORMAT, strtotime($dateTime));
    }

    /**
     *
     * @param Lending $myLending
     * @return ResponseInterface
     * @\TYPO3\CMS\Extbase\Annotation\Validate( param = "myLending", validator = "Cylancer\CyLending\Domain\Validator\LendingValidator" )
     */

    public function cancelAction(Lending $myLending = null)
    {
        $ceUid = $this->request->hasArgument(LendingController::CONTEXT_ELEMENT) ? $this->request->getArgument(LendingController::CONTEXT_ELEMENT) : $this->configurationManager->getContentObject()->data['uid'];

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validateCancel($myLending, $ceUid);
        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        if ($validationResults->isOkay()) {
            $validationResults->clear();
            $validationResults->addInfo('successful');

            $myLending->setState(Lending::STATE_CANCELED);
            $this->lendingRepository->update($myLending);
            $this->persistenceManager->persistAll();

            $this->informAboutCanceling($myLending);
        }

        $forwardArguments = [
            LendingController::TAB_KEY => LendingController::MY_LENDINGS_TAB,
            LendingController::CONTEXT_ELEMENT => $ceUid,
            LendingController::CANCEL_LENDING => $this->toArray($myLending),

        ];

        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }

    private function informAboutCanceling(Lending $myLending): void
    {

        /** @var FrontendUserGroup $g */
        $observers = $myLending->getObject()->getObserverGroup();
        $approvers = $myLending->getObject()->getApproverGroup();

        $groups =
            array_merge(
                $this->frontendUserService->getAllGroups($observers),
                $this->frontendUserService->getAllGroups($approvers)
            );
        if (!empty($groups)) {
            $frontendUserStorageUids = GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']);
            /** @var FrontendUser $receiver */
            foreach ($this->frontendUserRepository->getUsersOfGroups($groups, $frontendUserStorageUids) as $receiver) {
                if (!empty($receiver->getEmail())) {
                    $this->emailSendService->sendTemplateEmail(
                        [($receiver->getFirstName() . ' ' . $receiver->getLastName()) => $receiver->getEmail()],
                        [\TYPO3\CMS\Core\Utility\MailUtility::getSystemFromAddress()],
                        [],
                        LocalizationUtility::translate('message.cancel.email.subject', 'cy_lending'),
                        LendingController::INFORM_CANCEL_LENDING_MAIL_TEMPLATE,
                        LendingController::EXTENSION_NAME,
                        ['availabilityRequest' => $myLending, 'user' => $receiver]
                    );
                }
            }
        }
    }

    private function validateCancel(Lending $myLending, int $ceUid): ValidationResults
    {

        /** @var ValidationResults $validationResults */
        $validationResults = $myLending->getValidationResults();
        if ($myLending->getBorrower()->getUid() != $this->frontendUserService->getCurrentUserUid()) {
            $validationResults->addError('notYourLending');
        }

        return $validationResults;
    }

    /**
     *
     * @param Lending $toReserve
     * @return ResponseInterface
     * @\TYPO3\CMS\Extbase\Annotation\Validate( param = "toReserve", validator = "Cylancer\CyLending\Domain\Validator\LendingValidator" )
     */

    public function reserveAction(Lending $toReserve = null)
    {

        $ceUid = $this->request->hasArgument(LendingController::CONTEXT_ELEMENT) ? $this->request->getArgument(LendingController::CONTEXT_ELEMENT) : $this->configurationManager->getContentObject()->data['uid'];

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
        $validationResults = $this->validate($toReserve, $ceUid);
        if (
            $validationResults->isOkay() && (
                (LendingController::HIGH_PRIORITY_LENDING_AUTOMATIC_APPROVE && $toReserve->getHighPriority())
                || $toReserve->getObject()->getApproverGroup() == null)
        ) {
            // simulate an availability request...
            $state = $toReserve->getState();
            $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST); // only for the validation
            $this->validateApprove($toReserve, $ceUid, false);
            $toReserve->setState($state); // reset the state
        }

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];




        if ($validationResults->isOkay()) {
            $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST);
            $this->lendingRepository->add($toReserve);
            $this->persistenceManager->persistAll();

            if ((LendingController::HIGH_PRIORITY_LENDING_AUTOMATIC_APPROVE && $toReserve->getHighPriority()) || $toReserve->getObject()->getApproverGroup() == null) {
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
                $this->sendApproverEMails($toReserve);
            }
        }

        $forwardArguments[LendingController::TAB_KEY] = LendingController::LENDING_TAB;
        $forwardArguments[LendingController::CONTEXT_ELEMENT] = $ceUid;
        $forwardArguments[LendingController::TO_RESERVE] = $this->toArray($toReserve);
        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }

    private function sendApproverEMails(Lending $lending)
    {
        $contentObj = $this->configurationManager->getContentObject();
        $pageId = $contentObj->data['pid'];

        $approverGroup = $this->frontendUserService->getTopGroups($lending->getObject()->getApproverGroup());
        $frontendUserStorageUids = GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']);

        /** @var FrontendUser $receiver */
        foreach ($this->frontendUserRepository->getUsersOfGroups($approverGroup, $frontendUserStorageUids) as $receiver) {
            if (!empty($receiver->getEmail())) {
                $this->emailSendService->sendTemplateEmail(
                    [($receiver->getFirstName() . ' ' . $receiver->getLastName()) => $receiver->getEmail()],
                    [\TYPO3\CMS\Core\Utility\MailUtility::getSystemFromAddress()],
                    [],
                    LocalizationUtility::translate('message.approver.email.subject', 'cy_lending'),
                    LendingController::APPROVER_MAIL_TEMPLATE,
                    LendingController::EXTENSION_NAME,
                    ['pageUid' => $pageId, 'availabilityRequest' => $lending, 'user' => $receiver]
                );
            }
        }
    }

    /**
     *
     * @param Lending $availabilityRequest
     * @return ResponseInterface
     *  @\TYPO3\CMS\Extbase\Annotation\Validate( param = "availabilityRequest", validator = "Cylancer\CyLending\Domain\Validator\LendingValidator" )
     */

    public function rejectAction(Lending $availabilityRequest = null)
    {

        $ceUid = $this->request->hasArgument(LendingController::CONTEXT_ELEMENT) ? $this->request->getArgument(LendingController::CONTEXT_ELEMENT) : $this->configurationManager->getContentObject()->data['uid'];

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validateReject($availabilityRequest);

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
                LendingController::TAB_KEY => LendingController::APROVAL_TAB,

            ]);
            $this->sendAvailabilityRequestResultMail($availabilityRequest);
            $this->informObserverGroup($availabilityRequest);
        }

        $forwardArguments[LendingController::TAB_KEY] = LendingController::APROVAL_TAB;
        $forwardArguments[LendingController::CONTEXT_ELEMENT] = $ceUid;
        $forwardArguments[LendingController::REJECT_REQUEST] = $this->toArray($availabilityRequest);

        $forward = $forward->withArguments($forwardArguments);
        return $forward;
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
            if ($this->frontendUserService->contains($frontendUserGroup, $approverGroupUid)) {
                return true;
            }
        }
        return false;
    }

    private function validateReject(Lending $lending): ValidationResults
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

    /**
     *
     * @param Lending $availabilityRequest
     * @return ResponseInterface
     *  @\TYPO3\CMS\Extbase\Annotation\Validate( param = "availabilityRequest", validator = "Cylancer\CyLending\Domain\Validator\LendingValidator" )
     */

    public function approveAction(Lending $availabilityRequest = null): ForwardResponse
    {
        $ceUid = $this->request->hasArgument(LendingController::CONTEXT_ELEMENT)
            ? $this->request->getArgument(LendingController::CONTEXT_ELEMENT)
            : $this->configurationManager->getContentObject()->data['uid'];

        $allLendingStorageUids = array_merge(
            GeneralUtility::intExplode(',', $this->settings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $this->settings['otherLendingStorageUids'], TRUE)
        );

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

        if ($availabilityRequest == null && $this->request->hasArgument('availabilityRequest')) {
            $uid = intval($this->request->getArgument('availabilityRequest'));
            $availabilityRequest = $this->lendingRepository->findByUid($uid);
        }

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];

        /** @var ValidationResults $validationResults */

        $validationResults = $this->validateApprove($availabilityRequest, $ceUid);

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
                        $this->informPreviousBorrower($req);
                    }
                }
            }

            $this->persistenceManager->persistAll();
            $validationResults->clear();
            $validationResults->addInfo('successful.approved');

            $this->sendAvailabilityRequestResultMail($availabilityRequest);
            $this->informObserverGroup($availabilityRequest);
        } else {
            $forwardArguments[LendingController::TAB_KEY] = LendingController::APROVAL_TAB;
        }

        $forwardArguments[LendingController::TAB_KEY] = LendingController::APROVAL_TAB;
        $forwardArguments[LendingController::CONTEXT_ELEMENT] = $ceUid;
        $forwardArguments[LendingController::APPROVE_REQUEST] = $this->toArray($availabilityRequest);
        $forward = $forward->withArguments($forwardArguments);

        return $forward;
    }

    /**
     * @param Lending $availabilityRequest
     */
    private function informPreviousBorrower(Lending $availabilityRequest): void
    {
        $receiver = $availabilityRequest->getBorrower();
        if (!empty($receiver->getEmail())) {
            $this->emailSendService->sendTemplateEmail(
                [($receiver->getFirstName() . ' ' . $receiver->getLastName()) => $receiver->getEmail()],
                [\TYPO3\CMS\Core\Utility\MailUtility::getSystemFromAddress()],
                [],
                LocalizationUtility::translate('message.borrower.email.subject', 'cy_lending'),
                LendingController::INFORM_PREVIOUS_BORROWER_MAIL_TEMPLATE,
                LendingController::EXTENSION_NAME,
                ['availabilityRequest' => $availabilityRequest, 'user' => $receiver]
            );
        }
    }

    /**
     * @param Lending $availabilityRequest
     */
    private function sendAvailabilityRequestResultMail(Lending $availabilityRequest)
    {
        $receiver = $availabilityRequest->getBorrower();
        if (!empty($receiver->getEmail())) {
            $this->emailSendService->sendTemplateEmail(
                [($receiver->getFirstName() . ' ' . $receiver->getLastName()) => $receiver->getEmail()],
                [\TYPO3\CMS\Core\Utility\MailUtility::getSystemFromAddress()],
                [],
                LocalizationUtility::translate('message.borrower.email.subject', 'cy_lending'),
                LendingController::AVAILABILITY_REQUEST_RESULT_MAIL_TEMPLATE,
                LendingController::EXTENSION_NAME,
                ['availabilityRequest' => $availabilityRequest, 'user' => $receiver]
            );
        }
    }

    private function informObserverGroup(Lending $availabilityRequest)
    {
        $observers = $availabilityRequest->getObject()->getObserverGroup();
        if ($observers != null) {
            $groups = $this->frontendUserService->getAllGroups($observers);
            $frontendUserStorageUids = GeneralUtility::intExplode(',', $this->settings['frontendUserStroageUids']);
            /** @var FrontendUser $receiver */
            foreach ($this->frontendUserRepository->getUsersOfGroups($groups, $frontendUserStorageUids) as $receiver) {
                if (!empty($receiver->getEmail())) {
                    $this->emailSendService->sendTemplateEmail(
                        [($receiver->getFirstName() . ' ' . $receiver->getLastName()) => $receiver->getEmail()],
                        [\TYPO3\CMS\Core\Utility\MailUtility::getSystemFromAddress()],
                        [],
                        LocalizationUtility::translate('message.observer.email.subject', 'cy_lending'),
                        LendingController::OBSERVER_MAIL_TEMPLATE,
                        LendingController::EXTENSION_NAME,
                        ['availabilityRequest' => $availabilityRequest, 'user' => $receiver]
                    );
                }
            }
        }
    }

    private function validateApprove(Lending $lending, int $ceUid, $stored = true): ValidationResults
    {

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validate($lending, $ceUid);
        if ($stored && $this->lendingRepository->findByUid($lending->getUid()) == null) {
            $validationResults->addError('requestDeleted');
        } else {
            if (!$this->canApproveLendingObject($lending->getObject())) {
                $validationResults->addError('userNotApprover.approve');
                if ($lending->getHighPriority()) {
                    $validationResults->addError('userNotApprover.highPriorityNotAllowed');
                }
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

    /**
     * @param  Lending $lending
     */

    private function validate(Lending $lending, int $ceUid): ValidationResults
    {

        /** @var ValidationResults $validationResults */
        $validationResults = $lending->getValidationResults();
        if ($lending->getObject() == null) {
            $validationResults->addError('object.isEmpty');
        }

        $updateIsHighPriorityLendingPossible = $lending->getObject()->updateIsHighPriorityLendingPossible($this->frontendUserService);
        if ($lending->getHighPriority() && !$updateIsHighPriorityLendingPossible) {
            $validationResults->addError('highPriorityNotPossible');
        }

        $from = false;
        if ($lending->getFrom() == null) {
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
        if ($lending->getUntil() == null) {
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

            if (!empty($this->getSetting('reasonsForPreventionService', $ceUid))) {
                $reasonsForPreventionService = $this->getSetting('reasonsForPreventionService', $ceUid);
                $storageUids = GeneralUtility::intExplode(',', $this->getSetting('reasonsForPreventionServiceStorageUids', $ceUid), TRUE);

                $reasonsForPrevention = ($reasonsForPreventionService . '::reasonsForPreventionAction')
                (
                    $storageUids,
                    LendingRepository::stringDateTimetoDate($lending->getFrom()),
                    LendingRepository::stringDateTimetoDate($lending->getUntil())
                );

                if (count($reasonsForPrevention['data']) > 0) {
                    $list = '';
                    if (!empty($this->getSetting('reasonsForPreventionServiceTitle', $ceUid))) {
                        $list .= '<strong>' . $this->getSetting('reasonsForPreventionServiceTitle', $ceUid) . '</strong>';
                    }
                    $list .= '<ul>';
                    foreach ($reasonsForPrevention['data'] as $reason) {
                        $list .= '<li>' . $reason['title'] . '</li>';
                    }
                    $list .= '</ul>';
                    $validationResults->addWarning('from.reasonsForPrevention', [$list]);
                }
            }

            // individual objects can also be borrowed several times at the same time
            if ($lending->getObject()->getQuantity() == 1) {
                if (!$updateIsHighPriorityLendingPossible && !$lending->getHighPriority() && $this->lendingRepository->existsOverlapsAvailabilityRequests($lending)) {
                    $validationResults->addWarning('existsOverlapsAvailabilityRequests');
                }
                // if the item is present more than once, the maximum number of pieces may not be exceeded
            } else {

                $max = $this->lendingService->caluculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($lending));
                $available = $lending->getObject()->getQuantity() - $max;
                if ($available == 0) {
                    $validationResults->addError('quantity.notAvailable');
                } else if ($lending->getQuantity() > $available) {
                    $key = $available == 1 ? 'piece' : 'pieces';
                    $validationResults->addError('quantity.tooLarge', [
                        $available,
                        LocalizationUtility::translate($key, 'cy_lending')
                    ]);
                } else {
                    $max = $this->lendingService->caluculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($lending, LendingRepository::NO_LIMIT, Lending::STATE_AVAILABILITY_REQUEST));
                    if ($max > 0) {
                        $key = $max == 1 ? 'piece' : 'pieces';
                        $validationResults->addInfo('quantity.availabilityRequests', [
                            $max,
                            LocalizationUtility::translate($key, 'cy_lending')
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