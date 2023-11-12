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
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Cylancer\CyLending\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 *
 * This file is part of the 'lending' Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * ( c ) 2023 Clemens Gogolin <service@cylancer.net>
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

    const AVAILABILITY_REQUEST_VALIDATION_RESULTS_KEY = 'availabilityRequestValidationResults';
    const APPROVAL_VALIDATION_RESULTS_KEY = 'approvalValidationResults';
    const MY_LENDING_VALIDATION_RESULTS_KEY = 'myLendingValidationResults';
    const RESET_AVAILABILITY_REQUEST = 'resetAvailabilityRequest';
    const SUCCESSFUL_AVAILABILITY_REQUEST = 'successfulAvailabilityRequest';
    const OPEN_AVAILABILITY_REQUEST = 'openAvailabilityRequest';

    const ID_KEY = 'id';
    const TAB_KEY = 'tab';

    const APROVAL_TAB = 'approval';
    const LENDING_TAB = 'lending';
    const CALENDAR_TAB = 'calendar';
    const MY_LENDINGS_TAB = 'myLendings';

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

    /* @var ContentElementRepository */
    private ContentElementRepository $contentElementRepository;

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
        $this->frontendUserService = $frontendUserService;
        $this->persistenceManager = $persistenceManager;
        $this->emailSendService = $emailSendService;
        $this->lendingService = $lendingService;
    }

    /**
     * @return array
     */
    private function getAllCanApproveLendingObject()
    {
        $canApproveLendingObjects = [];
        $lendingObjects = $this->lendingObjectRepository->findAll();
        /** @var \Cylancer\CyLending\Domain\Model\LendingObject $lendingObject */
        /** @var \Cylancer\CyLending\Domain\Model\FrontendUserGroup $frontendUserGroup */
        foreach ($lendingObjects as $lendingObject) {
            /** @var \Cylancer\CyLending\Domain\Model\FrontendUserGroup $approverGroup */
            $approverGroup = $lendingObject->getApproverGroup();
            if ($approverGroup != null) {
                $approverGroupUid = $lendingObject->getApproverGroup()->getUid();
                foreach ($this->frontendUserService->getCurrentUser()->getUsergroup() as $frontendUserGroup) {
                    if ($this->frontendUserService->contains($frontendUserGroup, $approverGroupUid)) {
                        $canApproveLendingObjects[$lendingObject->getUid()] = $lendingObject;
                    }
                }
            }
        }
        return $canApproveLendingObjects;
    }


    private function getAllLendingStorageUids(): array
    {
        return array_merge(
            GeneralUtility::intExplode(',', $this->settings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $this->settings['otherLendingStorageUids'], TRUE)
        );

    }

    /**
     * @return ResponseInterface
     */
    public function showAction(): ResponseInterface
    {
        /** @var Lending $toReserve */
        $toReserve;
        // prepare
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $this->settings['lendingObjectStorageUids'], TRUE));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);

        $allLendingStorageUids = $this->getAllLendingStorageUids();

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

        $lendingObjects = $this->lendingObjectRepository->findAll();

        $canApproveLendingObjects = $this->getAllCanApproveLendingObject();

        $availabilityRequestValidationResults = $this->request->hasArgument(LendingController::AVAILABILITY_REQUEST_VALIDATION_RESULTS_KEY)
            ? $this->request->getArgument(LendingController::AVAILABILITY_REQUEST_VALIDATION_RESULTS_KEY)
            : new ValidationResults();

        $approvalValidationResults = $this->request->hasArgument(LendingController::APPROVAL_VALIDATION_RESULTS_KEY)
            ? $this->request->getArgument(LendingController::APPROVAL_VALIDATION_RESULTS_KEY)
            : new ValidationResults();


        if (!$availabilityRequestValidationResults->hasErrors() && !$availabilityRequestValidationResults->hasInfos()) {
            $toReserve = $this->createLending();
        } else {
            $toReserve = $this->fromArray($availabilityRequestValidationResults->getObject());
        }

        if ($toReserve->getObject() == null && !empty($lendingObjects)) {
            $toReserve->setObject($lendingObjects[0]);
        }

        $today = getdate();

        $tab = $this->request->hasArgument(LendingController::TAB_KEY)
            ? $this->request->getArgument(LendingController::TAB_KEY)
            : LendingController::CALENDAR_TAB;



        // active tab
        $allAvailabilityRequests = $this->lendingRepository->findAllAvailabilityRequests($canApproveLendingObjects);
        if (
            $approvalValidationResults->getObject() != null
            && isset($approvalValidationResults->getErrors()['error.existsOverlapsAvailabilityRequests'])
        ) {

            $tmp = $approvalValidationResults->getObject();
            foreach ($allAvailabilityRequests as $ar) {
                if ($tmp->getUid() === $ar->getUid()) {
                    $ar->setDisplayIgnoreOverlapping(true);
                }
            }
        }

        // tab page APPROVE:
        $this->view->assign(LendingController::APPROVAL_VALIDATION_RESULTS_KEY, $approvalValidationResults);
        $this->view->assign('availabilityRequests', $allAvailabilityRequests);
        // all availability request to approve / reject
        $this->view->assign('isApprover', !empty($canApproveLendingObjects));
        // is the approver tab visible?


        // tab page MY_LENDINGS:
        $this->view->assign(LendingController::APPROVAL_VALIDATION_RESULTS_KEY, $approvalValidationResults);
        $myLendings = $this->lendingRepository->findMyLendings($this->frontendUserService->getCurrentUser());
        // all availability request to approve / reject
        $this->view->assign('myLendings', $myLendings);

        // tab page AVAILABILITY_REQUEST:
        $displayReserveFormSummary = ($this->request->hasArgument(LendingController::SUCCESSFUL_AVAILABILITY_REQUEST)
            && $this->request->getArgument(LendingController::SUCCESSFUL_AVAILABILITY_REQUEST) === true);

        $this->view->assign('displayReserveFormSummary', $displayReserveFormSummary);
        $this->view->assign('lendingObjects', $lendingObjects);
        $this->view->assign(LendingController::AVAILABILITY_REQUEST_VALIDATION_RESULTS_KEY, $availabilityRequestValidationResults);
        // validation results
        $this->view->assign('purposes', empty(trim($this->settings['purposes'])) ? [] : explode('\n', $this->settings['purposes']));
        // form drafts of purposes
        $this->view->assign('toReserve', $toReserve);
        // form object
        $this->view->assign('untilOffset', "var untilOffset = '" . intval($this->settings['usualLendingTermHours']) . ':' . intval($this->settings['usualLendingTermMinutes']) . "';\n");
        
        $this->view->assign('existOverlappingLendingsUrl', $this->getExistOverlappingLendingsUrl());
       
        $reasonsForPreventionServiceUrl = $this->getReasonsForPreventionServiceUrl();
        if ($reasonsForPreventionServiceUrl !== false) {
            $this->view->assign('reasonsForPreventionServiceEnabled', true);
            $this->view->assign('reasonsForPreventionServiceUrl', $reasonsForPreventionServiceUrl);
        } else {
            $this->view->assign('reasonsForPreventionServiceEnabled', false);
            $this->view->assign('reasonsForPreventionServiceUrl', '');
        }

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
        if(count($myLendings) == 0 && $tab === LendingController::MY_LENDINGS_TAB){
            $tab = LendingController::CALENDAR_TAB;
        }

        $this->view->assign(LendingController::TAB_KEY, $tab);

        return $this->htmlResponse();
    }

    const T3_LINK_PREFIX = 't3://page?uid=';
    private function getExistOverlappingLendingsUrl(): bool|string
    {
        return $this->uriBuilder
            ->reset()
            ->setTargetPageType($this->settings['ajax']['pageType'])
            ->setNoCache(true)
            ->setCreateAbsoluteUri(true)
            ->uriFor(
                'existsEventOverlapping',
                ['uid'=>  $this->configurationManager->getContentObject()->data['uid'] ],
                'AjaxConnect',
            );
    }


    private function getReasonsForPreventionServiceUrl(): bool|string
    {
      //  debug($this->getReasonsForPreventionServicePageType());
        $pagetType = $this->getReasonsForPreventionServicePageType();
        if ($pagetType === false) {
            return false;
        }
        
        $target = $this->getReasonsForPreventionService();
        if ($target === false) {
            return false;
        }
      //  debug($target);
        return $this->uriBuilder
            ->reset()
            ->setTargetPageUid($target['page'])
            ->setTargetPageType($pagetType)
            ->setNoCache(true)
            ->setCreateAbsoluteUri(true)
            ->uriFor(
                'reasonsForPrevention',
                [
                    'id' => $target['contentElement'],
                ],
                $target['controller'],
                $target['extensionName'],
                $target['pluginName']
            );
    }
    /**
     * @return bool|int
     */

    private function getReasonsForPreventionService(): bool|array
    {
        $reasonsForPreventionContentElement = $this->settings['reasonsForPreventionContentElement'];
        
        if (empty($reasonsForPreventionContentElement)) {
            return false;
        }

        if (str_starts_with($reasonsForPreventionContentElement, LendingController::T3_LINK_PREFIX)) {
            $reasonsForPreventionContentElement = substr($reasonsForPreventionContentElement, strlen(LendingController::T3_LINK_PREFIX));
            $return = [];
            $tmp = explode('#', $reasonsForPreventionContentElement);
            if (count($tmp) != 2 || !is_numeric($tmp[0]) || !is_numeric($tmp[1])) {
                return false;
            } else {
                $return['page'] = intval($tmp[0]);
                $return['contentElement'] = intval($tmp[1]);
            }
            
            $ce = $this->contentElementRepository->findByUid(intval($return['contentElement']));
            if (
                $ce == null
                || empty($ce->getListType())
                || !isset($GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.'][$ce->getListType() . '.'])
            ) {
                return false;
            }
            
            $register = $GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.'][$ce->getListType() . '.'];
            $return['extensionName'] = $register['extensionName'];
            $return['pluginName'] = $register['pluginName'];

            $controllers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$register['extensionName']]['plugins'][$register['pluginName']]['controllers'];
            foreach ($controllers as $controllerConfig) {
                foreach ($controllerConfig['nonCacheableActions'] as $action) {
                    if ($action === 'reasonsForPrevention') {
                        $return['controller'] = $controllerConfig['alias'];
                    }
                }
            }
            if (!isset($return['controller'])) {
                // no controller with reasonsForPrevention action found
                return false;
            }
            return $return;
        }

        return false;
    }

    /**
     * @return bool|int
     */

    private function getReasonsForPreventionServicePageType(): bool|int
    {
        $reasonsForPreventionTSPageType = $this->settings['reasonsForPreventionTSPageType'];
        if (empty($reasonsForPreventionTSPageType)) {
            return false;
        }
        $path = explode('.', $reasonsForPreventionTSPageType);
        $typoScript = $GLOBALS['TSFE']->tmpl->setup;
        $count = count($path) - 1;
        for ($i = 0; $i < $count; $i++) {
            $typoScript = $typoScript[trim($path[$i]) . '.'];
        }
        $pagetType = $typoScript[trim($path[$count])];
        if (is_numeric($pagetType)) {
            return intval($pagetType);
        }
        return false;
    }

    private function createLending(): Lending
    {
        $return = new Lending();
        $now = time();
        $tomorrow = ((floor($now / (24 * 3600))) + 2) * (24 * 3600) + (12 * 3600);
        $return->setFrom(date('Y-m-d\TH:i', $tomorrow));
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
        /** @var ValidationResults $validationResults */
        $validationResults = $this->validateCancel($myLending);
        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        if (!$validationResults->hasErrors()) {

            $validationResults->addInfo('successful');
            $myLending->setState(Lending::STATE_CANCELED);
            $this->lendingRepository->update($myLending);
            $this->persistenceManager->persistAll();

            $this->informAboutCanceling($myLending);

        }

        $arguments = [
            LendingController::MY_LENDING_VALIDATION_RESULTS_KEY => $validationResults,
            LendingController::TAB_KEY => LendingController::MY_LENDINGS_TAB,
        ];
        $forward = $forward->withArguments($arguments);
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


    private function validateCancel(Lending $myLending): ValidationResults
    {

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validate($myLending);
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

        // prepare
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $this->settings['lendingObjectStorageUids'], TRUE));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);

        $allLendingStorageUids = $this->getAllLendingStorageUids();

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

        $toReserve->setBorrower($this->frontendUserService->getCurrentUser());
        $toReserve->setState(Lending::STATE_AVAILABILITY_REQUEST);
        $toReserve->setFrom($this->toDBDateTime($toReserve->getFrom()));
        $toReserve->setUntil($this->toDBDateTime($toReserve->getUntil()));
        $toReserve->setPid(intval($this->settings['lendingStorageUids']));

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validate($toReserve);

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];

        if (!$validationResults->hasErrors()) {
            $this->lendingRepository->add($toReserve);
            $this->persistenceManager->persistAll();
            $validationResults->addInfo('successful');

            if ($toReserve->getHighPriority() || $toReserve->getObject()->getApproverGroup() == null) {
                $toReserve = $this->lendingRepository->findByUid($toReserve->getUid());

                /** @var ForwardResponse $forward */
                $forward = $this->approveAction($toReserve);
                /** @var ValidationResults $vr */
                $vr = $forward->getArguments()[LendingController::APPROVAL_VALIDATION_RESULTS_KEY];
                if ($vr->hasErrors()) {
                    $validationResults->addError('automaticApproved');
                } else {
                    $validationResults->addInfo('automaticApproved');
                }
            } else {
                $this->sendApproverEMails($toReserve);
            }

            $forwardArguments[LendingController::SUCCESSFUL_AVAILABILITY_REQUEST] = true;
        } else {
            $toReserve->setPurpose('test');
        }

        $forwardArguments[LendingController::AVAILABILITY_REQUEST_VALIDATION_RESULTS_KEY] = $validationResults;
        $forwardArguments[LendingController::TAB_KEY] = LendingController::LENDING_TAB;

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

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validateReject($availabilityRequest);

        /** @var ForwardResponse $forward */
        $forward = new ForwardResponse('show');
        $forwardArguments = [];
        if (!$validationResults->hasErrors()) {
            $availabilityRequest->setState(Lending::STATE_REJECTED);
            $availabilityRequest->setApprover($this->frontendUserService->getCurrentUser());

            $this->lendingRepository->update($availabilityRequest);
            $this->persistenceManager->persistAll();

            $validationResults->addInfo('successful.rejected');
            $forward = $forward->withArguments([
                LendingController::APPROVAL_VALIDATION_RESULTS_KEY => $validationResults,
                LendingController::TAB_KEY => LendingController::APROVAL_TAB,

            ]);
            $this->sendAvailabilityRequestResultMail($availabilityRequest);
            $this->informObserverGroup($availabilityRequest);
        } else {
            $forwardArguments[LendingController::RESET_AVAILABILITY_REQUEST] = true;
        }

        $forwardArguments[LendingController::APPROVAL_VALIDATION_RESULTS_KEY] = $validationResults;
        $forwardArguments[LendingController::TAB_KEY] = LendingController::APROVAL_TAB;

        $forward = $forward->withArguments($forwardArguments);
        return $forward;
    }

    private function canApproveLendingObject(LendingObject $lendingObject): bool
    {
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
        $validationResults = $this->validate($lending);
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
        $validationResults = $this->validateApprove($availabilityRequest);

        if (!$validationResults->hasErrors()) {

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

            $validationResults->addInfo('successful.approved');

            $this->sendAvailabilityRequestResultMail($availabilityRequest);
            $this->informObserverGroup($availabilityRequest);
        } else {
            $forwardArguments[LendingController::TAB_KEY] = LendingController::APROVAL_TAB;
        }

        $forwardArguments[LendingController::APPROVAL_VALIDATION_RESULTS_KEY] = $validationResults;
        $forwardArguments[LendingController::TAB_KEY] = LendingController::APROVAL_TAB;

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

    private function validateApprove(Lending $lending): ValidationResults
    {

        /** @var ValidationResults $validationResults */
        $validationResults = $this->validate($lending);
        if ($this->lendingRepository->findByUid($lending->getUid()) == null) {
            $validationResults->addError('requestDeleted');
        } else {
            if (!$this->canApproveLendingObject($lending->getObject())) {
                $validationResults->addError('userNotApprover.approve');
                if ($lending->getHighPriority()) {
                    $validationResults->addError('userNotApprover.highPriorityNotAllowed');
                }
            }
            if (!$lending->getHighPriority() && !$lending->getIgnoreOverlapping() && $this->lendingRepository->existsOverlapsAvailabilityRequests($lending)) {
                $validationResults->addError('existsOverlapsAvailabilityRequests');
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

    private function validate(Lending $lending): ValidationResults
    {

        /** @var ValidationResults $validationResults */
        $validationResults = new ValidationResults($this->toArray($lending));
        if ($lending->getObject() == null) {
            $validationResults->addError('object.isEmpty');
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
            'object =' => $lending->getObject() != null ? $lending->getObject()->getUid() : null,
            'from' => $lending->getFrom(),
            'until' => $lending->getUntil(),
            'lendingAllowed' => $lending->getLendingAllowed(),
            'purpose' => $lending->getPurpose(),
            'state' => $lending->getState(),
            'highPriority' => $lending->getHighPriority(),
            'displayIgnoreOverlapping' => $lending->getDisplayIgnoreOverlapping(),
            'ignoreOverlapping' => $lending->getIgnoreOverlapping(),
        ];
    }


    public function fromArray($array): Lending
    {
        $return = isset($array['uid']) ? $this->lendingRepository->findByUid($array['uid']) : new Lending();
        $return->setBorrower(isset($array['borrower']) ? $this->frontendUserRepository->findByUid($array['borrower']) : null);
        $return->setApprover(isset($array['approver']) ? $this->frontendUserRepository->findByUid($array['approver']) : null);
        $return->setObject(isset($array['object']) ? $this->lendingObjectRepository->findByUid($array['object']) : null);
        $return->setFrom($array['from']);
        $return->setUntil($array['until']);
        $return->setLendingAllowed($array['lendingAllowed']);
        $return->setPurpose($array['purpose']);
        $return->setState($array['state']);
        $return->setHighPriority($array['highPriority']);
        $return->setDisplayIgnoreOverlapping($array['displayIgnoreOverlapping']);
        $return->setIgnoreOverlapping($array['ignoreOverlapping']);
        return $return;
    }
}