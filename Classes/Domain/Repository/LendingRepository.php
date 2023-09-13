<?php
namespace Cylancer\CyLending\Domain\Repository;

use Cylancer\CyLending\Domain\Model\Lending;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
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
    // {

    function findAllNotRejectedSince($until)
    {
        $today = date('Y-m-d H:i:s', time());
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
            'from' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
            'state' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
        ]);

        return $q->execute();
    }

    function findAllAvailabilityRequests(array $canApproveLendingObjects)
    {

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryInterface $q*/
        $q = $this->createQuery();
        $q->matching(
            $q->logicalAnd([
                $q->equals('state', Lending::STATE_AVAILABILITY_REQUEST),
                $q->logicalNot(
                    $q->lessThanOrEqual('until', date('Y-m-d H:i:s', time()))
                ),
                $q->in('object', array_keys($canApproveLendingObjects))
            ])
        );
        $q->setOrderings(['from' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);

        return $q->execute();
    }


    function findMonthAvailabilityRequests(int $year, int $month): QueryResult
    {
        $from = new \DateTimeImmutable();
        $from = $from->setDate($year, $month, 1)
            ->setTime(0, 0, 0, 0)
            ->format('Y-m-d H:i:s');

        $until = new \DateTimeImmutable();
        $until = $until->setDate($year, $month, 1)
            ->setTime(0, 0, 0, 0)
            ->add(new \DateInterval('P1M'))
            // ->sub(new \DateInterval('P1D'))
            ->format('Y-m-d H:i:s');

        $q = $this->createQuery();
        $q->matching(
            $q->logicalAnd([
                $q->logicalNot(
                    $q->logicalOr([
                        $q->lessThan('until', $from),
                        $q->greaterThanOrEqual('from', $until)
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
        return $q->execute();

    }



    function existsOverlapsAvailabilityRequests(Lending $lending): bool
    {
        $q = $this->createQuery();
        $q->setLimit(1)
            ->matching(
                $q->logicalAnd(
                    [
                        $q->logicalNot(
                            $q->equals('uid', $lending->getUid()),
                        ),
                        $q->equals('object', $lending->getObject()->getUid()),
                        $q->equals('state', Lending::STATE_APPROVED),
                        $q->logicalNot(
                            $q->logicalOr(
                                [
                                    $q->greaterThanOrEqual('from', $lending->getUntil()),
                                    $q->lessThanOrEqual('until', $lending->getFrom())
                                ]
                            )
                        )
                    ]
                )
            );

        return $q->count() > 0;
    }




}