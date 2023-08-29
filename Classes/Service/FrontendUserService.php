<?php
namespace Cylancer\CyLending\Service;

/**
 *
 * This file is part of the "user tools" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Service
 */
use TYPO3\CMS\Core\SingletonInterface;
use Cylancer\CyLending\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use Cylancer\CyLending\Domain\Model\FrontendUserGroup;
use Cylancer\CyLending\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;

class FrontendUserService implements SingletonInterface
{

    /** @var FrontendUserRepository   */
    private $frontendUserRepository = null;

     /**
     *
     * @param string $table
     * @return QueryBuilder
     */
    protected function getQueryBuilder(String $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }


    /**
     *
     * @param FrontendUserRepository $frontendUserRepository
     */
    public function __construct(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /**
     *
     * @return FrontendUser Returns the current frontend user
     */
    public function getCurrentUser(): bool|FrontendUser
    {
        if (!$this->isLogged()) {
            return false;
        }
        return $this->frontendUserRepository->findByUid($this->getCurrentUserUid());
    }

    /**
     * 
     * @return int
     */
    public function getCurrentUserUid(): int
    {
        if (!$this->isLogged()) {
            return false;
        }
        $context = GeneralUtility::makeInstance(Context::class);
        return $context->getPropertyFromAspect('frontend.user', 'id');
    }

    /**
     * Check if the user is logged
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }

    /**
     *
     * @param FrontendUserGroup $userGroup
     * @param integer $fegid
     * @param array $loopProtect
     * @return boolean
     */
    public function contains($userGroup, $feugid, &$loopProtect = array()): bool
    {
        if ($userGroup->getUid() == $feugid) {
            return true;
        } else {
            if (!in_array($userGroup->getUid(), $loopProtect)) {
                $loopProtect[] = $userGroup->getUid();
                foreach ($userGroup->getSubgroup() as $sg) {
                    if ($this->contains($sg, $feugid, $loopProtect)) {
                        return true;
                    }
                }
            }
            return false;
        }
    }

    /**
     *
     * @param FrontendUserGroup $userGroup
     * @param array $loopProtect
     * @return array
     */
    public function getAllGroups($userGroup, &$return = array()): array
    {
        if (!in_array($userGroup->getUid(), $return)) {
            $return[] = $userGroup->getUid();
            foreach ($userGroup->getSubgroup() as $sg) {
               $this->getAllGroups($sg, $return );
            }
        }
        return $return;
    }

 /**
     * Returns all groups from the frontend user to all his leafs in the hierachy tree...
     *
     * @param FrontendUser $frontendUser
     * @return array
     */
    public function getUserTopGroups(FrontendUser $frontendUser): array
    {
        $return = [];
        foreach ($frontendUser->getUsergroup() as $ug) {
            $return = array_merge($return, $this->_getTopGroups($ug->getUid(), $return));
        }
        return $return;
    }

    /**
     * Returns all groups from the frontend user group to all his leafs in the hierachy tree...
     *
     * @param FrontendUserGroup $userGroup
     * @return array
     */
    public function getTopGroups(FrontendUserGroup $userGroup): array
    {
        return $this->_getTopGroups($userGroup->getUid());
    }

    private function _getTopGroups(int $ug, array &$return = []): array
    {
        $return[] = $ug;
        $qb = $this->getQueryBuilder('fe_groups');
        $s = $qb->select('fe_groups.uid')
            ->from('fe_groups')
            ->where($qb->expr()
            ->inSet('subgroup', $ug))
            ->execute();
        while ($row = $s->fetch()) {
            $uid = intVal($row['uid']);
            if (! in_array($uid, $return)) {
                $return = array_unique(array_merge($return, $this->_getTopGroups($uid, $return)));
            }
        }
        return $return;
    }

}