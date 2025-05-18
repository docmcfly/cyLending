<?php
declare(strict_types=1);

namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as TYPO3AbstractEntity;

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
abstract class AbstractEntity extends TYPO3AbstractEntity
{


    protected ?ValidationResults $validationResults;


    protected array $contextValidationResults = [];


    public function __construct()
    {
        $this->validationResults = new ValidationResults();
    }

    public function getValidationResults(): ValidationResults
    {
        if (!isset($this->validationResults)) {
            $this->setValidationResults( new ValidationResults());
        }
        return $this->validationResults;
    }

    public function setValidationResults(ValidationResults $validationResults): self
    {
        $this->validationResults = $validationResults;
        return $this;
    }

    public function setContext(string $context): self
    {
        $this->contextValidationResults[$context] = $this->validationResults;
        $this->validationResults = new ValidationResults();
        return $this;
    }


    public function getContextValidationResults(): array
    {
        return $this->contextValidationResults;
    }
}
