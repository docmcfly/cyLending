<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
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
	const STATE_UNKNOWN = 0;		
	const STATE_AVAILABILITY_REQUEST = 1;		
	const STATE_APPROVED = 2;		
	const STATE_REJECTED = 3;



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

	/** @var boolean */
	protected $lendingAllowed = false;

	/** 
	 * @var string 
	 * */
	protected $purpose = '';

	/** @var int */
	protected $state = 0;



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
	 * @return boolean
	 */
	public function getLendingAllowed()
	{
		return $this->lendingAllowed;
	}

	/**
	 * 
	 * @param boolean $lendingAllowed 
	 * @return self
	 */
	public function setLendingAllowed($lendingAllowed): self
	{
		$this->lendingAllowed = $lendingAllowed;
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
	public function getState() {
		return $this->state;
	}
	
	/**
	 * 
	 * @param int $state 
	 * @return self
	 */
	public function setState($state): self {
		$this->state = $state;
		return $this;
	}

	/**
	 * 
	 * @return FrontendUser
	 */
	public function getApprover() {
		return $this->approver;
	}
	
	/**
	 * 
	 * @param FrontendUser $approver 
	 * @return self
	 */
	public function setApprover($approver): self {
		$this->approver = $approver;
		return $this;
	}
}