<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Repository\LendingObjectRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\LendingService;
use Cylancer\CyLending\Service\MiscService;
use Psr\Http\Message\ResponseInterface;
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
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */
class AjaxConnectController extends ActionController
{

    public function __construct(
        private readonly LendingService $lendingService,
        private readonly LendingObjectRepository $lendingObjectRepository,
        private readonly LendingRepository $lendingRepository,
        private readonly MiscService $miscService
    ) {
    }
    public function getEventsAction(array $lspid): ResponseInterface
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $year = intval($parsedBody['year']);
            $month = intval($parsedBody['month']);
            return $this->jsonResponse(
                json_encode(
                    $this->lendingService->getVisualAvailabilityRequestsAsEventsOf($year, $month, $lspid)));
        }
        return $this->jsonResponse(json_encode([]));
    }

    public function getMaxQuantityAction(int $uid): ResponseInterface
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

            $settings = $this->miscService->getFlexformSettings($uid);

            $storageUids = array_merge(
                GeneralUtility::intExplode(',', $settings['lendingStorageUids']),
                GeneralUtility::intExplode(',', $settings['otherLendingStorageUids']),
            );

            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storageUids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);

            // debug($tmp);
            return $this->jsonResponse(
                json_encode([
                'result' => $obj->getQuantity() - $this->lendingService->calculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($tmp)),
                'settings' => $settings
            ]));
        }
        return $this->jsonResponse(json_encode([]));
    }
}