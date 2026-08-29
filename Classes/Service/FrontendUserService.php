<?php
namespace Cylancer\CyLending\Service;

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

use TYPO3\CMS\Core\SingletonInterface;
use Cylancer\CyLending\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Context\Context;
use Cylancer\CyLending\Domain\Model\FrontendUserGroup;
use Cylancer\CyLending\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FrontendUserService implements SingletonInterface
{

    private const EXTENSION_NAME = 'cy_lending';

    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context

    ) {
    }

    public function getCurrentUser(): ?FrontendUser
    {
        if (!$this->isLogged()) {
            return null;
        }
        return $this->frontendUserRepository->findByUid($this->getCurrentUserUid());
    }

    public function getCurrentUserUid(): int
    {
        if (!$this->isLogged()) {
            return false;
        }
        return $this->context->getPropertyFromAspect('frontend.user', 'id');
    }

    public function isLogged(): bool
    {
        return $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }


    public function containsUser(FrontendUserGroup $userGroup, ?FrontendUser $frontendUser): bool
    {
        if ($frontendUser == null) {
            return false;
        }

        foreach ($frontendUser->getUsergroup() as $ug) {
            if ($this->containsGroup($userGroup, $ug->getUid())) {
                return true;
            }
        }

        return false;
    }

    public function containsGroup(FrontendUserGroup $userGroup, int $frontendUserGroupoUid, array &$loopProtect = []): bool
    {
        if ($userGroup->getUid() == $frontendUserGroupoUid) {
            return true;
        } else {
            if (!in_array($userGroup->getUid(), $loopProtect)) {
                $loopProtect[] = $userGroup->getUid();
                foreach ($userGroup->getSubgroup() as $sg) {
                    if ($this->containsGroup($sg, $frontendUserGroupoUid, $loopProtect)) {
                        return true;
                    }
                }
            }
            return false;
        }
    }

    public function getAllGroups(?FrontendUserGroup $userGroup, array &$return = []): array
    {
        if ($userGroup == null) {
            return [];
        } else if (!in_array($userGroup->getUid(), $return)) {
            $return[] = $userGroup->getUid();
            foreach ($userGroup->getSubgroup() as $sg) {
                $this->getAllGroups($sg, $return);
            }
        }
        return $return;
    }

    public function getUserTopGroups(FrontendUser $frontendUser): array
    {
        $return = [];
        foreach ($frontendUser->getUsergroup() as $ug) {
            $return = array_merge($return, $this->_getTopGroups($ug->getUid(), $return));
        }
        return $return;
    }

    public function getTopGroups(FrontendUserGroup $userGroup): array
    {
        return $this->_getTopGroups($userGroup->getUid());
    }

    private function _getTopGroups(int $ug, array &$return = []): array
    {
        $return[] = $ug;
        $qb = $this->connectionPool->getQueryBuilderForTable('fe_groups');
        $s = $qb->select('fe_groups.uid')
            ->from('fe_groups')
            ->where($qb->expr()
                ->inSet('subgroup', $ug))
            ->executeQuery();
        while ($row = $s->fetchAssociative()) {
            $uid = intVal($row['uid']);
            if (!in_array($uid, $return)) {
                $return = array_unique(array_merge($return, $this->_getTopGroups($uid, $return)));
            }
        }
        return $return;
    }

    public function getFrontendUserName(FrontendUser|int $frontendUser): string
    {
        if ($frontendUser == null) {
            return LocalizationUtility::translate('lending.overview.frontendUser.notfound', FrontendUserService::EXTENSION_NAME, ['null']);
        }

        if ($frontendUser instanceof FrontendUser) {
            return $frontendUser->getFirstName() . ' ' . $frontendUser->getLastName();
        }

        $uid = $frontendUser;
        $frontendUser = $this->frontendUserRepository->findFrontendUser($uid);

        if ($frontendUser == null) {
            return LocalizationUtility::translate('lending.overview.frontendUser.notfound', FrontendUserService::EXTENSION_NAME, [$uid]);
        }

        $sufix = $frontendUser->isDisabled()
            ? LocalizationUtility::translate('lending.overview.frontendUser.disable', FrontendUserService::EXTENSION_NAME, [$uid])
            : LocalizationUtility::translate('lending.overview.frontendUser.deleted', FrontendUserService::EXTENSION_NAME, [$uid]);

        return $frontendUser->getFirstName() . ' ' . $frontendUser->getLastName() . (empty($sufix) ? '' : ' ' . $sufix);

    }

}
