<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Validator;

use Cylancer\CyLending\Domain\Model\Lending;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * This file is part of the "Lending" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Clemens Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Validator
 */
class LendingValidator extends AbstractValidator
{

    protected function isValid($value){
      return true;    

    }
}