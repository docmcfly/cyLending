<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\LendingService;

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
class AllowedLendingObject
{

    /** @var Lending  */
    protected $lending;

    /** @var LendingObject  */
    protected $lendingObject;


    /** @var int */
    protected $availableQuantity = LendingObject::OBJECT_AVAILABILITY;


    public function __construct(Lending $lending, LendingObject $lendingObject)
    {
        $this->lending = $lending;
        $this->lendingObject = $lendingObject;
    }

    /** @var bool */
    protected $highPriorityLendingPossible = false;

    public function updateHighPriority(FrontendUserService $frontendUserService): void
    {
        $this->highPriorityLendingPossible = false;
        if (
            $this->lending == null
            || $this->lending->getBorrower() == null
            || $this->lendingObject->getHighPriorityGroup() == null
        ) {
            return;
        }

        $this->highPriorityLendingPossible = $frontendUserService->containsUser($this->lendingObject->getHighPriorityGroup(), $this->lending->getBorrower());
    }

    /**
     * @return bool
     */
    public function getHighPriorityLendingPossible():bool
    {
        return $this->highPriorityLendingPossible;
    }

    /**
     * 
     * @return int
     */
    public function getAvaiableQuantity(): int
    {
        return $this->availableQuantity == LendingObject::OBJECT_AVAILABILITY
            ? $this->lendingObject->getQuantity()
            : $this->availableQuantity;
    }

    /* 
     * @param int $availableQuantity 
     * @return self
     */
    public function setAvailableQuantity($availableQuantity): self
    {
        $this->availableQuantity = $availableQuantity;
        return $this;
    }


    public function updateAvailabilities(LendingService $lendingService): void
    {
        $max = $lendingService->calculateMaximumFrom($this->lending);
        $this->setAvailableQuantity($this->lendingObject->getQuantity() - $max);
    }



	/**
	 * 
	 * @return LendingObject
	 */
	public function getLendingObject() {
		return $this->lendingObject;
	}
}