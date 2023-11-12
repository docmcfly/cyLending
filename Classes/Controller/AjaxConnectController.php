<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Repository\ContentElementRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\LendingService;
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

    /* @var ContentElementRepository */
    private ContentElementRepository $contentElementRepository;



    public function __construct(
        LendingService $lendingService,
        LendingRepository $lendingRepository,
        ContentElementRepository $contentElementRepository
    ) {
        $this->lendingService = $lendingService;
        $this->lendingRepository = $lendingRepository;
        $this->contentElementRepository = $contentElementRepository;
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
            $settings = $this->getSourceSettings($uid);

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
     *
     * @param int $id
     * @throws \Exception
     * @return array
     */
    private function getSourceSettings(int $id): array
    {
        $piFlexform = null;
        /** @var \Cylancer\CyLending\Domain\Model\ContentElement $ce */
        $ce = $this->contentElementRepository->findByUid($id);
        if ($ce->getListType() != 'cylending_lending') {
            throw new \Exception($ce->getListType() . ' is not supported!');
        }
        $settings = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($ce->getPiFlexform())['data']['sDEF']['lDEF'];
        $return = [];
        $return['lendingStorageUids'] = $settings['settings.lendingStorageUids']['vDEF'];
        $return['otherLendingStorageUids'] = $settings['settings.otherLendingStorageUids']['vDEF'];

        return $return;
    }


}