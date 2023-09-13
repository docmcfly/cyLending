<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Controller\AjaxConnectController;
use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 *
 * This file is part of the "cy_lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Service
 *         
 */
class LendingService implements SingletonInterface
{

    /* @var LendingRepository */
    private LendingRepository $lendingRepository;


    public function __construct(
        LendingRepository $lendingRepository
    ) {
        $this->lendingRepository = $lendingRepository;
    }

    public function getAvailabilityRequestsAsEventsOf(int $year, int $month, array $storagePids = NULL): array
    {
        if ($storagePids != null) {
            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storagePids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);
        }

        $events = [];
        /** @var \Cylancer\CyLending\Domain\Model\Lending $lending*/
        foreach ($this->lendingRepository->findMonthAvailabilityRequests($year, $month) as $lending) {

            /** @var \Cylancer\CyLending\Domain\Model\LendingObject $lendingObject*/
            $lendingObject = $lending->getObject();

            $event = [];
            $event['idx'] = $lending->getUid();
            $event['start'] = $lending->getFrom();
            $event['end'] = $lending->getUntil();
            $event['title'] = $lendingObject->getTitle();
            $event['description'] = $lending->getPurpose();
            $event['responsible'] = $lending->getBorrower()->getFirstName() . ' ' . $lending->getBorrower()->getLastName();
            $event['backgroundColor'] = $lendingObject->getColor();
            $event['striped'] = $lending->getState() == Lending::STATE_AVAILABILITY_REQUEST;

            $events[] = $event;
        }
        return $events;


    }



}