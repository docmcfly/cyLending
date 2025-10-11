<?php

namespace Cylancer\CyLending\Controller;

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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;


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

class LendingController extends AbstractController
{


    private const string TO_RESERVE = 'toReserve';
    private const string APPROVE_REQUEST = 'approveRequest';
    private const string REJECT_REQUEST = 'rejectRequest';
    private const string CANCEL_LENDING = 'cancelLending';
    private const string CANCEL_AVAILABILITY_REQUEST = 'cancelAvailabilityRequest';
    private const string CONTENT_ELEMENT = 'contentElement';

    private const string TAB_KEY = 'tab';
    private const string LENDING_TAB = 'lending';
    private const string CALENDAR_TAB = 'calendar';
    private const string APPROVAL_TAB = 'approval';
    private const string MY_LENDINGS_TAB = 'myLendings';
    private const string MY_AVAILABILITY_REQUESTS_TAB = 'myAvailabilityRequests';

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
        parent::__construct(
            $lendingObjectRepository,
            $lendingRepository,
            $miscService,
            $frontendUserService,
            $siteFinder,
            $context,
        );

    }



    private function getSetting($key, ?int $uid = null): mixed
    {
        if ($uid != null && !isset($this->settings[$key])) {
            array_merge($this->settings, $this->miscService->getFlexformSettings($uid));
        }
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    private function getAllLendingStorageUids(array $flexformSettings): array
    {
        return array_merge(
            GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $flexformSettings['otherLendingStorageUids'], TRUE)
        );

    }

    private function getContentElementUid(): int
    {
        return $this->request->getAttribute('currentContentObject')->data['uid'];
    }

    public function showAction(): ResponseInterface
    {
        $ceUid = $this->getContentElementUid();

        $flexformSettings = $this->getFlexformSettings($ceUid);
        $this->prepareLendingRepository($flexformSettings);
        $this->prepareLendingObjectRepository($flexformSettings);

        $today = getdate();
        // active tab

        // COMMON
        $this->view->assign(LendingController::CONTENT_ELEMENT, value: $ceUid);

        $this->view->assign('appointmentSymbol', $flexformSettings['appointmentSymbol']);

        $this->view->assign('ceUid', $ceUid);

        // form object
        $this->view->assign('untilOffset', "var untilOffset = '" . intval($this->settings['usualLendingTermHours']) . ':' . intval($this->settings['usualLendingTermMinutes']) . "';\n");

        // for java script calculation of the until timestamp

        // tab page CALENDAR:

        $this->view->assign('language', $this->getLanguage());
        // language for the calendar.js API
        $this->view->assign(
            'currentMonthEvents',
            json_encode(
                // initilial data of the current month
                $this->lendingService->getVisualAvailabilityRequestsAsEventsOf(
                    $today['year'],
                    $today['mon'],
                    $this->getAllLendingStorageUids($flexformSettings),
                )
            )
        );

        $tab = $this->request->hasArgument(LendingController::TAB_KEY)
            ? $this->request->getArgument(LendingController::TAB_KEY)
            : LendingController::CALENDAR_TAB;

        $this->view->assign(LendingController::TAB_KEY, $tab);

        return $this->htmlResponse();
    }
}
