<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

use Cylancer\CyLending\Service\FrontendUserService;
use Cylancer\CyLending\Service\LendingService;

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
class AllowedLendingObject
{

    protected Lending $lending;

    protected LendingObject $lendingObject;

    protected int $availableQuantity = LendingObject::OBJECT_AVAILABILITY;


    public function __construct(Lending $lending, LendingObject $lendingObject)
    {
        $this->lending = $lending;
        $this->lendingObject = $lendingObject;
    }

    protected bool $highPriorityLendingPossible = false;

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

    public function getHighPriorityLendingPossible(): int
    {
        return $this->highPriorityLendingPossible ? 1 : 0;
    }

    public function getAvaiableQuantity(): int
    {
        return $this->availableQuantity == LendingObject::OBJECT_AVAILABILITY
            ? $this->lendingObject->getQuantity()
            : $this->availableQuantity;
    }

    public function setAvailableQuantity(int $availableQuantity): self
    {
        $this->availableQuantity = $availableQuantity;
        return $this;
    }
    public function updateAvailabilities(LendingService $lendingService): void
    {
        $max = $lendingService->calculateMaximumFrom($this->lending);
        $this->setAvailableQuantity($this->lendingObject->getQuantity() - $max);
    }
    public function getLendingObject(): LendingObject
    {
        return $this->lendingObject;
    }
}