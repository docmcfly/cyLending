<?php
namespace Cylancer\CyLending\Controller;

use Cylancer\CyLending\Domain\Repository\LendingObjectRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\MiscService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

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
class AbstractController extends ActionController
{

    public function __construct(
        private readonly LendingObjectRepository $lendingObjectRepository,
        private readonly LendingRepository $lendingRepository,
        private readonly MiscService $miscService,
        private readonly FrontendUserService $frontendUserService,
        private readonly SiteFinder $siteFinder,
        private readonly Context $context,
    ) {
    }


    protected function getFlexformSettings($ceUid): array
    {
        return array_merge($this->settings, $this->miscService->getFlexformSettings($ceUid));
    }

    protected function prepareLendingRepository(array $flexformSettings)
    {
        $allLendingStorageUids = array_merge(
            GeneralUtility::intExplode(',', $flexformSettings['lendingStorageUids'], TRUE),
            GeneralUtility::intExplode(',', $flexformSettings['otherLendingStorageUids'], TRUE)
        );

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds($allLendingStorageUids);
        $this->lendingRepository->setDefaultQuerySettings($querySettings);

    }


    protected function prepareLendingObjectRepository(array $flexformSettings)
    {

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(
            ',',
            $flexformSettings['lendingObjectStorageUids'],
            TRUE
        ));
        $this->lendingObjectRepository->setDefaultQuerySettings($querySettings);
        $this->lendingObjectRepository->setDefaultOrderings([
            'group_name' => QueryInterface::ORDER_ASCENDING,
            'title' => QueryInterface::ORDER_ASCENDING
        ]);

    }

    private function getPageId(): int
    {
        return $this->request->getAttribute('currentContentObject')->data['pid'];
    }

    protected function getLanguage(): string
    {
        return $this->siteFinder
            ->getSiteByPageId($this->getPageId())
            ->getLanguageById(
                $this->context->getPropertyFromAspect('language', 'id')
            )
            ->getLocale()
            ->getName();
    }

}