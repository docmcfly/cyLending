<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

use Cylancer\CyLending\Domain\Model\FrontendUser;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\LendingService;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

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

class Lending extends AbstractEntity
{
	public const STATE_UNKNOWN = 0;
	public const STATE_AVAILABILITY_REQUEST = 1;
	public const STATE_APPROVED = 2;
	public const STATE_REJECTED = 3;

	public const STATE_CANCELED = 4;

	protected ?FrontendUser $borrower = null;

	protected ?FrontendUser $approver = null;

	protected ?LendingObject $object = null;

	protected string $from = '0';

	protected string $until = '0';

	protected string $purpose = '';

	protected int $state = Lending::STATE_UNKNOWN;

	protected bool $highPriority = false;

	protected array $allowedObjects = [];

	protected int $quantity = 1;

	public function __construct()
	{
		parent::__construct();
	}

	public function setUid(int $uid): self
	{
		$this->uid = $uid;
		return $this;
	}

	public function getObject(): ?LendingObject
	{
		return $this->object;
	}

	public function setObject(?LendingObject $object): self
	{
		$this->object = $object;
		return $this;
	}

	public function getUntil(): string
	{
		return $this->until;
	}

	public function getUntilDate(): \DateTimeImmutable|bool
	{
		return \DateTimeImmutable::createFromFormat(LendingRepository::SQL_DATE_FORMAT, $this->until);
	}

	 public function setUntil(string $until): self
	{
		$this->until = $until;
		return $this;
	}

	public function getFrom(): string
	{
		return $this->from;
	}

	public function getFromDate(): \DateTimeImmutable|bool
	{
		return \DateTimeImmutable::createFromFormat(LendingRepository::SQL_DATE_FORMAT, $this->from);
	}

	public function setFrom(string $from): self
	{
		$this->from = $from;
		return $this;
	}

	 public function getBorrower(): ?FrontendUser
	{
		return $this->borrower;
	}

	public function setBorrower(?FrontendUser $borrower): self
	{
		$this->borrower = $borrower;
		return $this;
	}

	public function getPurpose(): ?string
	{
		return $this->purpose;
	}

	public function setPurpose(string $purpose): self
	{
		$this->purpose = $purpose;
		return $this;
	}

	public function getState(): int
	{
		return $this->state;
	}

	public function setState(int $state): self
	{
		$this->state = $state;
		return $this;
	}

	public function getApprover(): ?FrontendUser
	{
		return $this->approver;
	}

	public function setApprover(?FrontendUser $approver): self
	{
		$this->approver = $approver;
		return $this;
	}

	public function getHighPriority(): bool
	{
		return $this->highPriority;
	}

	public function setHighPriority(bool $highPriority): self
	{
		$this->highPriority = $highPriority;
		return $this;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	public function setQuantity(int $quantity): self
	{
		$this->quantity = $quantity;
		return $this;
	}

	public function getAllowedObjects(): array
	{
		return $this->allowedObjects;
	}

	public function setAllowedObjects(QueryResultInterface|array $lendingObjects): void
	{
		$this->allowedObjects = [];
		foreach ($lendingObjects as $lendingObject) {
			$this->addAllowedObject($lendingObject);
		}
	}


	public function addAllowedObject(LendingObject $lendingObject): AllowedLendingObject
	{
		$allowedObject = new AllowedLendingObject($this, $lendingObject);
		$this->allowedObjects[] = $allowedObject;
		return $allowedObject;
	}

	public function updateAllowedObjects(FrontendUserService $frontendUserService, LendingService $lendingService): void
	{
		/** @var AllowedLendingObject $allowedObject */
		foreach ($this->allowedObjects as $allowedObject) {
			$allowedObject->updateHighPriority($frontendUserService);
			$allowedObject->updateAvailabilities($lendingService);
		}
	}

	public function getCanHighPriorityLend(): bool
	{
		/** @var AllowedLendingObject $allowedObject */
		foreach ($this->allowedObjects as $allowedObject) {
			if ($allowedObject->getHighPriorityLendingPossible()) {
				return true;
			}
		}
		return false;
	}

	public function getMaxQuantity(): int
	{
		/** @var AllowedLendingObject $allowedObject */
		$max = 0;
		foreach ($this->allowedObjects as $allowedObject) {
			$max = max($max, $allowedObject->getLendingObject()->getQuantity());
		}
		return $max;
	}

}
