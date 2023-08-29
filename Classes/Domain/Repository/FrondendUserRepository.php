<?php
namespace Cylancer\CyLending\Domain\Repository;

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
class FrontendUserRepository extends Repository
{



    public function getUsersOfGroups(array $usergroups, array $storageUids)
    {
        $q = $this->createQuery();

        $findInSet = [];
        
        foreach ($usergroups as $ugUid) {
            $findInSet[] =  $q->contains('usergroup', $ugUid);
        }

        $q->getQuerySettings()->setStoragePageIds($storageUids);
        $q->matching(
            $q->logicalOr($findInSet)
        );
        return $q->execute();

    }

}