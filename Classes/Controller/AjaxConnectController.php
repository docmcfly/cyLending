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

    const LIST_TYPE = 'cylancer_cylending';

    /* @var LendingRepository */
    private LendingService $lendingService;


    public function __construct(
        LendingService $lendingService
    ) {
        $this->lendingService = $lendingService;
    }


     /**
     * @param string $cid
     */
    public function getEventsAction(String $cid){
        debug($cid);

        $parsedBody = $this->request->getParsedBody();
        if(is_array($parsedBody)){
            $year = intval($parsedBody['year']);
            $month = intval($parsedBody['month']);
            return json_encode($this->lendingService->getAvailabilityRequestsAsEventsOf(2023,9));
        }
        debug($currentPid = $GLOBALS['TSFE']->id);
        
        return json_encode($this->lendingService->getAvailabilityRequestsAsEventsOf(2023,9));

    }




    /**
     *
     * @param string $table
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder(String $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

    /**
     *
     * @param string $id
     * @throws \Exception
     * @return array
     */
    private function getStorageUid(String $id): array
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

/*

https://development.ortsfeuerwehr-letter.de/eigenentwicklungen/lending?
no_cache=1&
tx_cylending_lending%5Baction%5D=getEvents&
tx_cylending_lending%5Bcontroller%5D=ajaxConnect&
tx_cylending_lending%5Bmonth%5D=10&
tx_cylending_lending%5Byear%5D=2023&
type=778&
cHash=7ae8a39a76b565c55929e2c47839b22a
*/