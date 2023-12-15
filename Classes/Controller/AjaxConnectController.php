<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Repository\LendingObjectRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\LendingService;
use Cylancer\CyLending\Service\MiscService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 *
 * This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 Clemens Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Controller
 */
class AjaxConnectController extends ActionController
{

    const LIST_TYPE = 'cylending_lending';

    /* @var LendingService */
    private LendingService $lendingService;

    /* @var LendingRepository */
    private LendingRepository $lendingRepository;

    /* @var LendingRepository */
    private LendingObjectRepository $lendingObjectRepository;

    /* @var MiscService */
    private MiscService $miscService;



    public function __construct(
        LendingService $lendingService,
        LendingObjectRepository $lendingObjectRepository,
        LendingRepository $lendingRepository,
        MiscService $miscService
    ) {
        $this->lendingService = $lendingService;
        $this->lendingRepository = $lendingRepository;
        $this->lendingObjectRepository = $lendingObjectRepository;
        $this->miscService = $miscService;
    }

    /**
     * @param array $lspid
     */
    public function getEventsAction(array $lspid)
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $year = intval($parsedBody['year']);
            $month = intval($parsedBody['month']);
            return json_encode($this->lendingService->getVisualAvailabilityRequestsAsEventsOf($year, $month, $lspid));
        }
        return json_encode([]);
    }

    /**
     * @param int  $uid
     */
    public function existsEventOverlappingAction(int $uid)
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $from = $parsedBody['from'];
            $until = $parsedBody['until'];
            $tmp = new Lending();
            $tmp->setFrom($from);
            $tmp->setUntil($until);

            $settings = $this->miscService->getFlexformSettings($uid, 'lendingStorageUids', 'otherLendingStorageUids');

            $storageUids = array_merge(
                GeneralUtility::intExplode(',', $settings['lendingStorageUids']),
                GeneralUtility::intExplode(',', $settings['otherLendingStorageUids']),
            );

            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storageUids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);

            // debug($tmp);
            return json_encode([
                'result' => $this->lendingRepository->existsOverlapsAvailabilityRequests($tmp),
                'debug' => var_export($tmp, true),
            ]);
        }
        return json_encode([]);
    }

    /**
     * @param int  $uid
     */
    public function getMaxQuantityAction(int $uid)
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $from = $parsedBody['from'];
            $until = $parsedBody['until'];
            $objUid = intval($parsedBody['object']);
            $tmp = new Lending();
            $tmp->setFrom($from);
            $tmp->setUntil($until);
            $obj = $this->lendingObjectRepository->findByUid($objUid);
            $tmp->setObject($obj);

            $settings = $this->miscService->getFlexformSettings($uid, 'lendingStorageUids', 'otherLendingStorageUids');

            $storageUids = array_merge(
                GeneralUtility::intExplode(',', $settings['lendingStorageUids']),
                GeneralUtility::intExplode(',', $settings['otherLendingStorageUids']),
            );

            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storageUids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);

            // debug($tmp);
            return json_encode([
                'result' => $obj->getQuantity()- $this->lendingService->caluculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($tmp))
            ]);


        }


    }


}