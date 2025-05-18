<?php
declare(strict_types=1);
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

 class LendingObject extends AbstractEntity
{
	public const OBJECT_AVAILABILITY = -1;

	/** @var string */
	protected ?string $groupName = '';

	/** @var string */
	protected ?string $title = '';

	/** @var string */
	protected ?string $color = '';
	protected ?FrontendUserGroup $approverGroup = null;
	protected ?FrontendUserGroup $observerGroup = null;
	protected ?FrontendUserGroup $highPriorityGroup = null;
	protected int $quantity = 1;
	public function getTitle():?string 
	{
		return $this->title;
	}
	 public function setTitle(?string $title): self
	{
		$this->title = $title;
		return $this;
	}

	public function getColor():?string 
	{
		return $this->color;
	}

	public function setColor(?string $color): self
	{
		$this->color = $color;
		return $this;
	}

	public function  getApproverGroup() :?FrontendUserGroup
	{
		return $this->approverGroup;
	}

	public function setApproverGroup(?FrontendUserGroup $approverGroup): self
	{
		$this->approverGroup = $approverGroup;
		return $this;
	}

	public function getObserverGroup() :?FrontendUserGroup
	{
		return $this->observerGroup;
	}

	public function setObserverGroup(?FrontendUserGroup $observerGroup): self
	{
		$this->observerGroup = $observerGroup;
		return $this;
	}

	public function getGroupName() :?string
	{
		return $this->groupName;
	}

	public function setGroupName(?string $groupName): self
	{
		$this->groupName = $groupName;
		return $this;
	}

	public function getQuantity():int
	{
		return $this->quantity;
	}

	public function setQuantity(int $quantity): self
	{
		$this->quantity = $quantity;
		return $this;
	}

	public function getHighPriorityGroup():?FrontendUserGroup
	{
		return $this->highPriorityGroup;
	}

	public function setHighPriorityGroup(?FrontendUserGroup $highPriorityGroup): self
	{
		$this->highPriorityGroup = $highPriorityGroup;
		return $this;
	}

}
