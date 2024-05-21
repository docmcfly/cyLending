<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

use Cylancer\CyLending\Service\FrontendUserService;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This file is part of the "Lending" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2024 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Model
 */
class LendingObject extends AbstractEntity
{


	public const OBJECT_AVAILABILITY = -1;

	/** @var string */
	protected $groupName = '';

	/** @var string */
	protected $title = '';

	/** @var string */
	protected $color = '';

	/** @var FrontendUserGroup */
	protected $approverGroup = null;

	/** @var FrontendUserGroup */
	protected $observerGroup = null;

	/** @var FrontendUserGroup */
	protected $highPriorityGroup = null;

	/** @var int */
	protected $quantity = 1;


	/**
	 * 
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * 
	 * @param string $title 
	 * @return self
	 */
	public function setTitle($title): self
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * 
	 * @return string
	 */
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * 
	 * @param string $color 
	 * @return self
	 */
	public function setColor($color): self
	{
		$this->color = $color;
		return $this;
	}

	/**
	 * 
	 * @return FrontendUserGroup
	 */
	public function getApproverGroup()
	{
		return $this->approverGroup;
	}

	/**
	 * 
	 * @param FrontendUserGroup $approverGroup 
	 * @return self
	 */
	public function setApproverGroup($approverGroup): self
	{
		$this->approverGroup = $approverGroup;
		return $this;
	}

	/**
	 * 
	 * @return FrontendUserGroup
	 */
	public function getObserverGroup()
	{
		return $this->observerGroup;
	}

	/**
	 * 
	 * @param FrontendUserGroup $observerGroup 
	 * @return self
	 */
	public function setObserverGroup($observerGroup): self
	{
		$this->observerGroup = $observerGroup;
		return $this;
	}



	/**
	 * 
	 * @return string
	 */
	public function getGroupName()
	{
		return $this->groupName;
	}

	/**
	 * 
	 * @param string $groupName 
	 * @return self
	 */
	public function setGroupName($groupName): self
	{
		$this->groupName = $groupName;
		return $this;
	}

	/**
	 * 
	 * @return int
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * 
	 * @param int $quantity 
	 * @return self
	 */
	public function setQuantity($quantity): self
	{
		$this->quantity = $quantity;
		return $this;
	}


	
	/**
	 * 
	 * @return FrontendUserGroup
	 */
	public function getHighPriorityGroup()
	{
		return $this->highPriorityGroup;
	}

	/**
	 * 
	 * @param FrontendUserGroup $highPriorityGroup 
	 * @return self
	 */
	public function setHighPriorityGroup($highPriorityGroup): self
	{
		$this->highPriorityGroup = $highPriorityGroup;
		return $this;
	}


}
