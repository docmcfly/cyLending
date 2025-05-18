<?php
namespace Cylancer\CyLending\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

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

 class FrontendUserRepository extends Repository
{



    public function getUsersOfGroups(array $usergroups, array $storageUids): array|QueryResultInterface
    {
        $q = $this->createQuery();

        $findInSet = [];
        
        foreach ($usergroups as $ugUid) {
            $findInSet[] =  $q->contains('usergroup', $ugUid);
        }

        $q->getQuerySettings()->setStoragePageIds($storageUids);
        $q->matching(
            $q->logicalOr(...$findInSet)
        );
        return $q->execute();

    }

}