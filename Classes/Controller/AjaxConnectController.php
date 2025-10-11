<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Model\ValidationResults;
use Cylancer\CyLending\Domain\Repository\LendingObjectRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\LendingService;
use Cylancer\CyLending\Service\MiscService;
use Cylancer\CyLending\Service\SendService;
use Cylancer\CyLending\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
class AjaxConnectController extends AbstractController
{

    private const string TEMPLATE = 'Templates';
    private const string PARTIAL = 'Partials';

    public function __construct(
        private readonly LendingService $lendingService,
        private readonly LendingObjectRepository $lendingObjectRepository,
        private readonly LendingRepository $lendingRepository,
        private readonly MiscService $miscService,
        private readonly SiteFinder $siteFinder,
        private readonly Context $context,
        private readonly ValidationService $validationService,
        private readonly FrontendUserService $frontendUserService,
        private readonly SendService $sendService,
        private readonly PersistenceManager $persistenceManager,
    ) {
        parent::__construct(
            $lendingObjectRepository,
            $lendingRepository,
            $miscService,
            $frontendUserService,
            $siteFinder,
            $context
        );
    }

    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function getReserveFormAction(int $ceUid): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);
        $this->prepareLendingObjectRepository($flexformSettings);
        $toReserve = $this->createLending();
        $result = [];

        $result['before'] = $toReserve->toArray();
        $result['rendering'] = $this->render(
            'ReserveForm',
            [
                "settings" => $flexformSettings,
                "ceUid" => $ceUid,
                "prefix" => "lending.overview.availabilityRequest.form",
                "toReserve" => $toReserve,
                "context" => 'reserceAvailabilityRequest',
                'purposes' => empty(trim($flexformSettings['purposes'])) ? [] : explode("\n", $flexformSettings['purposes']),
            ]
        );

        return $this->jsonResponse(json_encode($result));
    }

    /**
     * @param Lending $toReserve
     * @param int $ceUid
     * @param string[] $confirmedWarnings
     * @return ResponseInterface
     */
    public function reserveAction(Lending $toReserve, int $ceUid, array $confirmedWarnings = []): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        $validationResults = $this->lendingService->reserve(
            $toReserve,
            $confirmedWarnings,
            $ceUid,
            $this->request,
            $this->getLanguage(),
            $flexformSettings,

        );

        $result['result'] = $validationResults->isOkay();

        $result['rendering'] =
            $this->renderPartial(
                'ValidationResults',
                [
                    "prefix" => "lending.overview.availabilityRequest.form",
                    "object" => $toReserve,
                    "context" => 'toReserve',
                    "confirmedWarnings" => $confirmedWarnings,
                ]
            );

        $result['toReserve'] = ($result['result'] ? $this->createLending() : $toReserve)->toArray();

        return $this->jsonResponse(json_encode($result));
    }

    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function getMyLendingsAction(int $ceUid): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        $result = [];
        $result['rendering'] = $this->render(
            'MyLendings',
            [
                "settings" => $flexformSettings,
                "ceUid" => $ceUid,
                "prefix" => "lending.overview.myLending.form",
                "myLendings" => $this->lendingRepository->findMyLendings($this->frontendUserService->getCurrentUser()),
                "context" => 'cancelLending'
            ]
        );
        return $this->jsonResponse(json_encode($result));
    }

    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function cancelMyLendingAction(int $ceUid, int $uid): ResponseInterface
    {
        $result['result'] = false;
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        /** @var Lending $myLending */
        $myLending = $this->lendingRepository->findByUid($uid);

        if (isset($myLending)) {

            $validationResults = $this->lendingService->cancelMyLendingAction(
                $myLending,
                $this->request,
                $this->getLanguage(),
                GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE),
            );

            $result['result'] = $validationResults->isOkay();
            $result['rendering'] =
                $this->renderPartial(
                    'ValidationResults',
                    [
                        "prefix" => "lending.overview.myLending.form",
                        "object" => $myLending,
                        "context" => 'cancelMyLending'
                    ]
                );
        }
        return $this->jsonResponse(json_encode($result));
    }

    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function getMyAvailabilityRequestsAction(int $ceUid): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        $result = [];
        $result['rendering'] = $this->render(
            'MyAvailabilityRequests',
            [
                "settings" => $flexformSettings,
                "ceUid" => $ceUid,
                "prefix" => "lending.overview.myAvailabilityRequest.form",
                "myAvailabilityRequests" => $this->lendingRepository->findMyAvailabilityRequests($this->frontendUserService->getCurrentUser()),
                "context" => 'cancelAvailabilityRequest'
            ]
        );
        return $this->jsonResponse(json_encode($result));
    }


    public function cancelMyAvailabilityRequestAction(int $ceUid, int $uid): ResponseInterface
    {
        $result['result'] = false;
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        /** @var Lending $myAvailabilityRequest */
        $myAvailabilityRequest = $this->lendingRepository->findByUid($uid);

        if (isset($myAvailabilityRequest)) {

            /** @var ValidationResults $validationResults */
            $validationResults = $this->lendingService->cancelMyAvailabilityRequest(
                $myAvailabilityRequest,
                $this->request,
                $this->getLanguage(),
                GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE),
            );

            $result['result'] = $validationResults->isOkay();
            $result['rendering'] =
                $this->renderPartial(
                    'ValidationResults',
                    [
                        "prefix" => "lending.overview.myAvailabilityRequest.form",
                        "object" => $myAvailabilityRequest,
                        "context" => 'cancelMyAvailabilityRequest'
                    ]
                );
        }
        return $this->jsonResponse(json_encode($result));

    }

    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function isAvailabilityRequestsVisibleAction(int $ceUid): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingObjectRepository($flexformSettings);

        $result['isVisible'] = !empty($this->getAllCanApproveLendingObject());

        return $this->jsonResponse(json_encode($result));
    }


    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function getAvailabilityRequestsAction(int $ceUid): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);
        $this->prepareLendingObjectRepository($flexformSettings);

        $canApproveLendingObjects = $this->getAllCanApproveLendingObject();
        $result = [];
        $result['rendering'] = $this->render(
            'ApproveForm',
            [
                "settings" => $flexformSettings,
                "ceUid" => $ceUid,
                "prefix" => "lending.overview.availabilityRequest.form",
                "availabilityRequests" => $this->lendingRepository->findAllFutureAvailabilityRequests($canApproveLendingObjects),
            ]
        );
        return $this->jsonResponse(json_encode($result));
    }
    /**
     * @param int $ceUid
     * @param int $uid
     * @param string[] $confirmedWarnings
     * @return ResponseInterface
     */
    public function approveAction(int $ceUid, int $uid, array $confirmedWarnings = []): ResponseInterface
    {
        $result = ['result' => false];
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        /** @var Lending $availabilityRequest */
        $availabilityRequest = $this->lendingRepository->findByIdentifier($uid);
        if (isset($availabilityRequest)) {

            $validationResults = $this->lendingService->approve(
                $availabilityRequest,
                $confirmedWarnings,
                $ceUid,
                $this->request,
                $this->getLanguage(),
                $flexformSettings
            );

            $result['result'] = $validationResults->isOkay();
            $result['confirmedWarnings'] = $confirmedWarnings;
            $result['rendering'] =
                $this->renderPartial(
                    'ValidationResults',
                    [
                        "prefix" => "lending.overview.approval.form",
                        "object" => $availabilityRequest,
                        "context" => 'approval'
                    ]
                );

        }

        return $this->jsonResponse(json_encode($result));
    }


    /**
     * @param int $ceUid
     * @param int $uid
     * @return ResponseInterface
     */
    public function rejectAction(int $ceUid, int $uid): ResponseInterface
    {
        $result = ['result' => false];
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);

        /** @var Lending $availabilityRequest */
        $availabilityRequest = $this->lendingRepository->findByIdentifier($uid);

        if (isset($availabilityRequest)) {

            $validationResults = $this->lendingService->reject(
                $availabilityRequest,
                $this->request,
                $this->getLanguage(),
                $flexformSettings,
            );
            $result['result'] = $validationResults->isOkay();
            $result['rendering'] =
                $this->renderPartial(
                    'ValidationResults',
                    [
                        "prefix" => "lending.overview.approval.form",
                        "object" => $availabilityRequest,
                        "context" => 'approval'
                    ]
                );
        }
        return $this->jsonResponse(json_encode($result));
    }



    private function createLending(): Lending
    {
        $return = new Lending();
        $now = time();
        $tomorrow = ((floor($now / (24 * 3600))) + 2) * (24 * 3600) + (12 * 3600);
        $return->setFrom(date('Y-m-d\TH:i', $tomorrow));
        $return->setValidationResults(new ValidationResults());
        $return->setBorrower($this->frontendUserService->getCurrentUser());
        $return->setAllowedObjects($this->lendingObjectRepository->findAll());
        $allowedObjects = $return->getAllowedObjects();
        $return->setObject(empty($allowedObjects) ? null : $allowedObjects[0]->getLendingObject());
        $return->updateAllowedObjects($this->frontendUserService, $this->lendingService);

        return $return;
    }
    private function getAllLendingStorageUids(array $flexformSettings): array
    {
        return array_merge(
            GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $flexformSettings['otherLendingStorageUids'], TRUE)
        );

    }


    private function createStandaloneView(): StandaloneView
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setRequest($this->request);
        $view->setLayoutRootPaths(["EXT:cy_lending/Resources/Private/Layouts/"]);
        $view->setPartialRootPaths(["EXT:cy_lending/Resources/Private/Partials/"]);
        $view->setTemplateRootPaths(["EXT:cy_lending/Resources/Private/Templates/"]);
        return $view;

    }


    private function render(string $template, array $variables = []): string
    {
        $view = $this->createStandaloneView();
        $view->setTemplate($template);
        $view->assignMultiple($variables);
        return $view->render();
    }

    private function renderPartial(string $partial, array $variables = []): string
    {
        $view = $this->createStandaloneView();
        return $view->renderPartial($partial, null, $variables);
    }



    public function getEventsAction(int $ceUid): ResponseInterface
    {
        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);


        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $year = intval($parsedBody['year']);
            $month = intval($parsedBody['month']);
            return $this->jsonResponse(
                json_encode(
                    $this->lendingService->getVisualAvailabilityRequestsAsEventsOf($year, $month, GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE))
                )
            );
        }
        return $this->jsonResponse(json_encode([]));
    }

    /**
     * @param int $ceUid
     * @return ResponseInterface
     */
    public function getMaxQuantityAction(int $ceUid): ResponseInterface
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $from = $parsedBody['from'];
            $until = $parsedBody['until'];
            $objUid = intval($parsedBody['object']);
            $tmp = new Lending();
            $tmp->setFrom($from);
            $tmp->setUntil($until);

            /** @var \Cylancer\CyLending\Domain\Model\LendingObject $obj */
            $obj = $this->lendingObjectRepository->findByUid($objUid);
            $tmp->setObject($obj);

            $flexformSettings = $this->getFlexformSettings($ceUid);

            $storageUids = array_merge(
                GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids']),
                GeneralUtility::intExplode(',', $flexformSettings['otherLendingStorageUids']),
            );

            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storageUids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);

            return $this->jsonResponse(
                json_encode([
                    'result' => $obj->getQuantity() - $this->lendingService->calculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($tmp)),
                    'settings' => $flexformSettings
                ])
            );
        }
        return $this->jsonResponse(json_encode([]));
    }


    private function getAllCanApproveLendingObject(): array
    {
        $canApproveLendingObjects = [];
        /** @var \Cylancer\CyLending\Domain\Model\FrontendUserGroup $frontendUserGroup */
        foreach ($this->lendingObjectRepository->findAll() as $lendingObject) {
            /** @var \Cylancer\CyLending\Domain\Model\LendingObject $lendingObject */
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

}