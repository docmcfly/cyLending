<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Service\LendingService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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

    /* @var LendingRepository */
    private LendingService $lendingService;


    public function __construct(
        LendingService $lendingService
    ) {
        $this->lendingService = $lendingService;
    }



    /**
     * @param int $cid
     */
    public function getEventsAction(int $cid = 0)
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $year = intval($parsedBody['year']);
            $month = intval($parsedBody['month']);
            return json_encode($this->lendingService->getAvailabilityRequestsAsEventsOf($year, $month, $this->getStoragePids($cid)));
        }
        return json_encode([]);
    }






    /**
     *
     * @param string $table
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder(string $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

    /**
     *
     * @param string $id
     * @throws \Exception
     * @return array
     */
    private function getStoragePids(string $id): array
    {
        $qb = $this->getQueryBuilder('tt_content');
        $s = $qb->select('list_type', 'pages', 'pi_flexform')
            ->from('tt_content')
            ->where($qb->expr()
                ->eq('uid', $qb->createNamedParameter($id)))
            ->execute();
        if ($row = $s->fetchAssociative()) {
            $contentElement = $row;
        } else {
            throw new \Exception("Content element $id found.");
        }
        if ($row = $s->fetchAssociative()) {
            throw new \Exception("Two content elements with $id found? Database corrupt?");
        }
        if ($contentElement['list_type'] == AjaxConnectController::LIST_TYPE) {
            return GeneralUtility::intExplode(',', $contentElement['pages'], TRUE);
        }
        return [];
    }



}