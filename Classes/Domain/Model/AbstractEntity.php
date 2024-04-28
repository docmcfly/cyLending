<?php
declare(strict_types=1);

namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as TYPO3AbstractEntity;

/* This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2024 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Model
 */
abstract class AbstractEntity extends TYPO3AbstractEntity
{

    /**
     * @var ValidationResults
     */

    protected $validationResults;


    /**
     * @var array
     */
     protected $contextValidationResults = [];


    public function __construct()
    {
        $this->validationResults = new ValidationResults();
    }

    /**
     * 
     * @return ValidationResults
     */
    public function getValidationResults()
    {
        if($this->validationResults === null)
        {
            $this->validationResults = new ValidationResults();
        }   
        return $this->validationResults;
    }

    /**
     * 
     * @param ValidationResults $validationResults 
     * @return self
     */
    public function setValidationResults($validationResults): self
    {
        $this->validationResults = $validationResults;
        return $this;
    }

    /**
     * 
     * @param string $context 
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->contextValidationResults[$context] = $this->validationResults;
        $this->validationResults = new ValidationResults();
        return $this;
    }


	/**
	 * 
	 * @return array
	 */
	public function getContextValidationResults() {
		return $this->contextValidationResults;
	}
}
