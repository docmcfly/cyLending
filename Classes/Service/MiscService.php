<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Domain\Repository\ContentElementRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class MiscService implements SingletonInterface
{


    public function __construct(
        private readonly ContentElementRepository $contentElementRepository
    ) {
    }

    public function getFlexformSettings(int $uid)
    {

        /** @var \Cylancer\CyLending\Domain\Model\ContentElement $ce */
        $ce = $this->contentElementRepository->findByUid($uid);
        if ($ce->getListType() != 'cylending_lending') {
            throw new \Exception($ce->getListType() . ' is not supported!');
        }
        $return = [];
        foreach (GeneralUtility::xml2array($ce->getPiFlexform())['data'] as $cat) {
            foreach ($cat['lDEF'] as $key => $value) {
                $return[substr($key, 9)] = $value ['vDEF'];
            }
        }
         return $return;
    }

}