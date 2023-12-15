<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

/**
 *
 * This file is part of the "CyLending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Model
 *         
 */
class ValidationResults
{

    /**
     *
     * @var array
     */
    protected $infos;

    /** @var array  */
    protected $errors;

    /** @var array  */
    protected $warnings;

    /** @var array  */
    protected $confirmedWarnings;


    /**
     *
     */
    public function __construct()
    {
        $this->infos = [];
        $this->errors = [];
        $this->warnings = [];
        $this->confirmedWarnings = [];
    }

    /** @return array of stings  */
    public function getInfos()
    {
        return $this->infos;
    }

    /** @return array of strings  */
    public function getErrors()
    {
        return $this->errors;
    }

    /** @return array of srings   */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     *
     * @return array of strings
     */
    public function hasInfos(): bool
    {
        return !empty($this->infos);
    }

    /**
     * @return array of strings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function isOkay(): bool
    {
        if ($this->hasErrors()) {
            return false;
        }
        if ($this->hasWarnings()) {
            foreach ($this->warnings as $warning) {
                if (!in_array($warning['id'], $this->confirmedWarnings)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *
     * @param string $errorKey
     * @param array $arguments
     */
    public function addInfo(string $infoKey, array $arguments = []): void
    {
        $keySplit = explode('.', $infoKey, 2);
        $this->infos['info.' . $infoKey]['arguments'] = $arguments;
        $this->infos['info.' . $infoKey]['id'] = count($keySplit) == 2 ? $keySplit[0] : $infoKey;
    }

    /**
     *
     */
    public function addError(string $errorKey, array $arguments = []): void
    {
        $keySplit = explode('.', $errorKey, 2);
        $this->errors['error.' . $errorKey]['arguments'] = $arguments;
        $this->errors['error.' . $errorKey]['id'] = count($keySplit) == 2 ? $keySplit[0] : $errorKey;
    }

    /**
     * 
     * @param string $warningKey
     * @param array $arguments
     */
    public function addWarning(string $warningKey, array $arguments = []): void
    {
        $keySplit = explode('.', $warningKey, 2);
        $this->warnings['warning.' . $warningKey]['arguments'] = $arguments;
        $this->warnings['warning.' . $warningKey]['id'] = count($keySplit) == 2 ? $keySplit[0] : $warningKey;
    }


    /**
     *
     * @param array $infos
     */
    public function addInfos(array $infos)
    {
        foreach ($infos as $info) {
            $this->addInfo($info);
        }
    }

    /**
     *
     * @param array $errors
     * 
     */
    public function addErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    /**
     * @param array $warnings
     */
    public function addWarnings(array $warnings)
    {
        foreach ($warnings as $warning) {
            $this->addWarning($warning);
        }
    }

    public function isConfirmed(string $warningKey): bool
    {
        return in_array($warningKey, $this->confirmedWarnings);
    }

    /**
     * 
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * 
     * @return array
     */
    public function getConfirmedWarnings()
    {
        return $this->confirmedWarnings;
    }

    /**
     * 
     * @param array $confirmedWarnings 
     * @return self
     */
    public function setConfirmedWarnings($confirmedWarnings): self
    {
        $this->confirmedWarnings = $confirmedWarnings;
        return $this;
    }

    public function clear(): void
    {
        $this->infos = [];
        $this->errors = [];
        $this->warnings = [];
        $this->confirmedWarnings = [];
    }
}
