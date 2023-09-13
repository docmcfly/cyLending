<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Service\LendingService;
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
     * @param array $lspid
     */
    public function getEventsAction(array $lspid)
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody)) {
            $year = intval($parsedBody['year']);
            $month = intval($parsedBody['month']);
            return json_encode($this->lendingService->getAvailabilityRequestsAsEventsOf($year, $month, $lspid));
        }
        return json_encode([]);
    }

}