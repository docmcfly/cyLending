<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Model\LendingObject;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 *
 * This file is part of the "cy_lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2024 C. Gogolin <service@cylancer.net>
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

    public function getVisualAvailabilityRequestsAsEventsOf(int $year, int $month, array $storagePids = NULL): array
    {
        if ($storagePids != null) {
            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setStoragePageIds($storagePids);
            $this->lendingRepository->setDefaultQuerySettings($querySettings);
        }

        /** @var \DateTime $startDate */
        $startDate = LendingRepository::toDateTime($year, $month);
        $startDate = LendingRepository::addDays($startDate, 1 - intval($startDate->format("N")));

        /** @var \DateTime $endDate */
        $endDate = LendingRepository::toDateTime($year, $month);
        $endDate = LendingRepository::addMonths($endDate, 1);
        $endDate = LendingRepository::addDays($endDate, 7 - intval($endDate->format("N")));

        $events = [];
        /** @var Lending $lending*/
        foreach ($this->lendingRepository->findFromUntilAvailabilityRequests($startDate, $endDate) as $lending) {

            /** @var LendingObject $lendingObject*/
            $lendingObject = $lending->getObject();
            $event = [];
            $event['idx'] = $lending->getUid();
            $event['start'] = $lending->getFrom();
            $event['end'] = $lending->getUntil();
            $event['title'] = ($lending->getHighPriority() ? '❗' : '') . $lendingObject->getTitle();
            $description = $lending->getPurpose();
            if ($lendingObject->getQuantity() > 1) {
                $key = $lending->getQuantity() == 1 ? 'piece' : 'pieces';
                $description .= ' (' . $lending->getQuantity() . ' ' . LocalizationUtility::translate($key, 'cy_lending') . ')';
            }
            $event['description'] = $description;
            $event['responsible'] = $lending->getBorrower()->getFirstName() . ' ' . $lending->getBorrower()->getLastName();
            $event['backgroundColor'] = $lendingObject->getColor();
            $event['striped'] = $lending->getState() == Lending::STATE_AVAILABILITY_REQUEST;

            $events[] = $event;
        }
        return $events;
    }

    public function calculateMaximumFrom(Lending $lending): int
    {
         return $this->calculateMaximum($this->lendingRepository->getOverlapsAvailabilityRequests($lending));
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|object[] $lendings
     */
    public function calculateMaximum(  $lendings): int
    {

        $o = null;
        $switchMoments = [];
        /** @var Lending $lending */
        foreach ($lendings as $lending) {
            if ($o == null) {

            }
            $q = $lending->getQuantity();
            $f = $lending->getFrom();
            $u = $lending->getUntil();

            if (isset($switchMoments[$f])) {
                $switchMoments[$f] += $q;
            } else {
                $switchMoments[$f] = $q;
            }
            if (isset($switchMoments[$u])) {
                $switchMoments[$u] -= $q;
            } else {
                $switchMoments[$u] = 0 - $q;
            }
        }
        ksort($switchMoments);

        /** @var int $maximum */
        $maximum = 0;

        $qtyState = 0;
        foreach ($switchMoments as $qty) {
            $qtyState += $qty;
            $maximum = max($maximum, $qtyState);
            // debug($qtyState, 'qtyState');
            // debug($maximum, 'maximum');
        }

        return $maximum;

    }


}
