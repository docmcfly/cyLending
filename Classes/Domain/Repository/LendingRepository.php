<?php
namespace Cylancer\CyLending\Domain\Repository;

use Cylancer\CyLending\Domain\Model\FrontendUser;
use Cylancer\CyLending\Domain\Model\Lending;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 *
 * This file is part of the "Lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 Clemens Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Repository
 *         
 */
class LendingRepository extends Repository
{
    const SQL_DATE_FORMAT = "Y-m-d H:i:s";
    const DATE_FORMAT = "Y-m-d";

    public function findAllNotRejectedSince($until)
    {
        $today = date(LendingRepository::SQL_DATE_FORMAT, time());
        $this->persistenceManager->clearState();

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryInterface $q*/
        $q = $this->createQuery();
        $q->matching(
            $q->logicalOr([
                $q->logicalAnd([
                    $q->equals('state', Lending::STATE_AVAILABILITY_REQUEST),

                    $q->logicalNot(
                        $q->lessThanOrEqual('from', $today)
                    )
                ]),

                $q->logicalAnd([
                    $q->equals('state', Lending::STATE_APPROVED),

                    $q->logicalNot(
                        $q->lessThanOrEqual('until', $until)
                    )
                ])
            ]),
        );
        $q->setOrderings([
            'from' => QueryInterface::ORDER_ASCENDING,
            'state' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $q->execute();
    }

    public function findAllAvailabilityRequests(array $canApproveLendingObjects)
    {

        /** @var QueryInterface $q*/
        $q = $this->createQuery();
        $q->matching(
            $q->logicalAnd([
                $q->equals('state', Lending::STATE_AVAILABILITY_REQUEST),
                $q->logicalNot(
                    $q->lessThanOrEqual('until', date(LendingRepository::SQL_DATE_FORMAT, time()))
                ),
                $q->in('object', array_keys($canApproveLendingObjects))
            ])
        );
        $q->setOrderings(['from' => QueryInterface::ORDER_ASCENDING]);

        return $q->execute();
    }



    public function findMyLendings(?FrontendUser $frontendUser): ?QueryResult
    {
        if ($frontendUser == null) {
            return null;
        }
       
        /** @var QueryInterface $q*/
        $q = $this->createQuery();
        $q->matching(
            $q->logicalAnd([
                $q->equals('state', Lending::STATE_APPROVED),
                $q->logicalNot(
                    $q->lessThanOrEqual('from', date(LendingRepository::SQL_DATE_FORMAT, time()))
                ),
                $q->equals('borrower', $frontendUser->getUid())
            ])
        );
        $q->setOrderings(['from' => QueryInterface::ORDER_ASCENDING]);
    // $queryParser = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
    //      debug($queryParser->convertQueryToDoctrineQueryBuilder($q)->getSQL());
        return $q->execute();
    }



    public function findFromUntilAvailabilityRequests(\DateTime $from, \DateTime $until): QueryResult
    {
        $q = $this->createQuery();
        $q->matching(
            $q->logicalAnd([
                $q->logicalNot(
                    $q->logicalOr([
                        $q->lessThan('until', $from->format(LendingRepository::SQL_DATE_FORMAT)),
                        $q->greaterThanOrEqual('from', $until->format(LendingRepository::SQL_DATE_FORMAT))
                    ])
                ),
                $q->logicalOr(
                    [
                        $q->equals("state", Lending::STATE_AVAILABILITY_REQUEST),
                        $q->equals("state", Lending::STATE_APPROVED)
                    ]
                )
            ])
        );
        $q->setOrderings([
            'high_priority' => QueryInterface::ORDER_DESCENDING,
            'from' => QueryInterface::ORDER_ASCENDING,
            'purpose' => QueryInterface::ORDER_ASCENDING,
        ]);
        return  $q->execute();

    }

    /**
     * @deprecated 
     */
    public function findMonthAvailabilityRequests(int $year, int $month): QueryResult
    {
        $from = LendingRepository::toDateTime($year, $month);
        $until = LendingRepository::addMonths($from, 1);

        return $this->findFromUntilAvailabilityRequests($from, $until);

    }

    public function existsOverlapsAvailabilityRequests(Lending $lending): bool
    {
        return count($this->getOverlapsAvailabilityRequests($lending, 1)->toArray()) > 0;
    }

    public function getOverlapsAvailabilityRequests(Lending $lending, int $limit = 0)
    {
        $q = $this->createQuery();

        if ($limit > 0) {
            $q->setLimit($limit);
        }

        $conditions = [
            $q->equals('state', Lending::STATE_APPROVED),
            $q->logicalNot(
                $q->logicalOr(
                    [
                        $q->greaterThanOrEqual('from', $lending->getUntil()),
                        $q->lessThanOrEqual('until', $lending->getFrom())
                    ]
                )
            )
        ];

        if ($lending->getUid() != null) {
            $conditions[] = $q->logicalNot(
                $q->equals('uid', $lending->getUid()),
            );
        }

        if ($lending->getObject() != null) {
            $conditions[] = $q->equals('object', $lending->getObject()->getUid());
        }

        $q->matching($q->logicalAnd($conditions));

        return $q->execute();
        // $queryParser = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
        // return $queryParser->convertQueryToDoctrineQueryBuilder($q)->getSQL();


    }

    public static function stringDatetoDateTime(string $date): \DateTime
    {
        $parseResult = date_parse_from_format(LendingRepository::DATE_FORMAT, $date);
        if ($parseResult['error_count'] > 0) {
            throw new \Exception("Date format is invalid: " . $date);
        }

        $return = new \DateTime();
        return $return
            ->setDate($parseResult['year'], $parseResult['month'], $parseResult['day'])
            ->setTime(0, 0, 0, 0);
    }


    public static function toDateTime(int $year, int $month, int $day = 1): \DateTime
    {
        $return = new \DateTime();
        return $return
            ->setDate($year, $month, $day)
            ->setTime(0, 0, 0, 0);
    }

    public static function addMonths(\DateTime $date, int $offset = 1): \DateTime
    {
        $interval = new \DateInterval('P' . abs($offset) . 'M');
        return $offset < 0 ? $date->sub($interval) : $date->add($interval);
    }
    public static function addDays(\DateTime $date, int $offset = 1): \DateTime
    {
        $interval = new \DateInterval('P' . abs($offset) . 'D');
        return $offset < 0 ? $date->sub($interval) : $date->add($interval);
    }



}