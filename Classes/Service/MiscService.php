<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Domain\Repository\ContentElementRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class MiscService implements SingletonInterface
{

    /* @var ContentElementRepositoy */
    private ContentElementRepository $contentElementRepository;


    public function __construct(
        ContentElementRepository $contentElementRepository
    ) {
        $this->contentElementRepository = $contentElementRepository;
    }

    public function getFlexformSettings(int $uid, String... $keys){

        $piFlexform = null;
        /** @var \Cylancer\CyLending\Domain\Model\ContentElement $ce */
        $ce = $this->contentElementRepository->findByUid($uid);
        if ($ce->getListType() != 'cylending_lending') {
            throw new \Exception($ce->getListType() . ' is not supported!');
        }
        $settings = GeneralUtility::xml2array($ce->getPiFlexform())['data']['sDEF']['lDEF'];
        $return = [];
        foreach($keys as $key){
            if(isset($settings['settings.'.$key]) && isset($settings['settings.'.$key]['vDEF'])) {
                $return[$key] = $settings['settings.'.$key]['vDEF'];        
            }
        }
        return $return;
    }

}