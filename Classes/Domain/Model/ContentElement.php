<?php
namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 *
 * This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 Clemens Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Model
 */
class ContentElement extends AbstractEntity
{

    /**
     * @var string
     */
    protected $listType;

    /**
     * @var string
     */
    protected $piFlexform;


    /**
     * 
     * @return string
     */
    public function getListType()
    {
        return $this->listType;
    }

    /**
     * 
     * @param string $listType 
     * @return self
     */
    public function setListType($listType): self
    {
        $this->listType = $listType;
        return $this;
    }

	/**
	 * 
	 * @return string
	 */
	public function getPiFlexform() {
		return $this->piFlexform;
	}
}