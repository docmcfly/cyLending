<?php
namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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

class FrontendUserGroup extends AbstractEntity
{

    protected ?string $title = '';

    /** @var ObjectStorage<FrontendUserGroup> */
    protected $subgroup;

    /**
     * Constructs a new Frontend User Group
     */
    public function __construct()
    {
        $this->subgroup = new ObjectStorage();
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string|null
    {
        return $this->title;
    }

    public function setSubgroup(ObjectStorage $subgroup): void
    {
        $this->subgroup = $subgroup;
    }

    public function addSubgroup(FrontendUserGroup $subgroup): void
    {
        $this->subgroup->attach($subgroup);
    }

    public function removeSubgroup(FrontendUserGroup $subgroup): void
    {
        $this->subgroup->detach($subgroup);
    }

    public function getSubgroup(): ObjectStorage
    {
        return $this->subgroup;
    }
}
