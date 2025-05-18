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
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */
class ContentElement extends AbstractEntity
{

    /**
     * @var string
     */
    protected ?string $listType;

    /**
     * @var string
     */
    protected ?string $piFlexform;


    /**
     * 
     * @return string
     */
    public function getListType(): ?string
    {
        return $this->listType;
    }

    public function setListType(?string $listType): self
    {
        $this->listType = $listType;
        return $this;
    }

    public function getPiFlexform(): ?string
    {
        return $this->piFlexform;
    }
}