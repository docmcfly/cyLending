<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

use Cylancer\CyLending\Domain\Model\FrontendUser;

/**
 * This file is part of the "Lending" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Clemens Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Model
 */
class Lending extends AbstractEntity
{
	public const STATE_UNKNOWN = 0;
	public const STATE_AVAILABILITY_REQUEST = 1;
	public const STATE_APPROVED = 2;
	public const STATE_REJECTED = 3;

	public const STATE_CANCELED = 4;


	/** @var FrontendUser */
	protected $borrower = '';

	/** @var FrontendUser */
	protected $approver = '';

	/** @var LendingObject */
	protected $object = null;

	/** 
	 * @var string 
	 * */
	protected $from = '0';

	/** 
	 * @var string
	 * */
	protected $until = '0';


	/** 
	 * @var string 
	 * */
	protected $purpose = '';

	/** @var int */
	protected $state = Lending::STATE_UNKNOWN;

	/** @var bool */
	protected $highPriority = false;

	/** @var int */
	protected $quantity = 1;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 
	 * @param int $uid 
	 * @return self
	 */
	public function setUid($uid): self
	{
		$this->uid = $uid;
		return $this;
	}

	/**
	 * 
	 * @return LendingObject|null
	 */
	public function getObject(): ?LendingObject
	{
		return $this->object;
	}

	/**
	 * 
	 * @param LendingObject $object 
	 * @return self
	 */
	public function setObject(?LendingObject $object): self
	{
		$this->object = $object;
		return $this;
	}

	/**
	 * @return string 
	 */
	public function getUntil()
	{
		return $this->until;
	}

	/**
	 * @param string $until 
	 * @return self
	 */
	public function setUntil(string $until): self
	{
		$this->until = $until;
		return $this;
	}

	/**
	 * 
	 * @return string
	 */
	public function getFrom(): string
	{
		return $this->from;
	}

	/**
	 * 
	 * @param string $from 
	 * @return self
	 */
	public function setFrom(string $from): self
	{
		$this->from = $from;
		return $this;
	}


	/**
	 * 
	 * @return FrontendUser
	 */
	public function getBorrower()
	{
		return $this->borrower;
	}

	/**
	 * 
	 * @param FrontendUser $borrower 
	 * @return self
	 */
	public function setBorrower($borrower): self
	{
		$this->borrower = $borrower;
		return $this;
	}

	/**
	 * 
	 * @return string
	 */
	public function getPurpose()
	{
		return $this->purpose;
	}

	/**
	 * 
	 * @param string $purpose 
	 * @return self
	 */
	public function setPurpose($purpose): self
	{
		$this->purpose = $purpose;
		return $this;
	}

	/**
	 * 
	 * @return int
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * 
	 * @param int $state 
	 * @return self
	 */
	public function setState($state): self
	{
		$this->state = $state;
		return $this;
	}

	/**
	 * 
	 * @return FrontendUser
	 */
	public function getApprover()
	{
		return $this->approver;
	}

	/**
	 * 
	 * @param FrontendUser $approver 
	 * @return self
	 */
	public function setApprover($approver): self
	{
		$this->approver = $approver;
		return $this;
	}

	/**
	 * 
	 * @return bool
	 */
	public function getHighPriority()
	{
		return $this->highPriority;
	}

	/**
	 * 
	 * @param bool $highPriority 
	 * @return self
	 */
	public function setHighPriority($highPriority): self
	{
		$this->highPriority = $highPriority;
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


}