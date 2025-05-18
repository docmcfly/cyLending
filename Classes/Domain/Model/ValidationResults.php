<?php
declare(strict_types=1);
namespace Cylancer\CyLending\Domain\Model;

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

class ValidationResults
{

    protected array $infos = [];

    protected array $errors = [];

    protected array $warnings = [];

    protected array $confirmedWarnings = [];



    public function getInfos(): array
    {
        return $this->infos;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasInfos(): bool
    {
        return !empty($this->infos);
    }

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

    public function addInfo(string $infoKey, array $arguments = []): void
    {
        $keySplit = explode('.', $infoKey, 2);
        $this->infos["info.$infoKey"]['arguments'] = $arguments;
        $this->infos["info.$infoKey"]['id'] = count($keySplit) == 2 ? $keySplit[0] : $infoKey;
    }

    public function addError(string $errorKey, array $arguments = []): void
    {
        $keySplit = explode('.', $errorKey, 2);
        $this->errors["error.$errorKey"]['arguments'] = $arguments;
        $this->errors["error.$errorKey"]['id'] = count($keySplit) == 2 ? $keySplit[0] : $errorKey;
    }

    public function addWarning(string $warningKey, array $arguments = []): void
    {
        $keySplit = explode('.', $warningKey, 2);
        $this->warnings["warning.$warningKey"]['arguments'] = $arguments;
        $this->warnings["warning.$warningKey"]['id'] = count($keySplit) == 2 ? $keySplit[0] : $warningKey;
    }


    public function addInfos(array $infos): void
    {
        foreach ($infos as $info) {
            $this->addInfo($info);
        }
    }

    public function addErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    public function addWarnings(array $warnings): void
    {
        foreach ($warnings as $warning) {
            $this->addWarning($warning);
        }
    }

    public function isConfirmed(string $warningKey): bool
    {
        return in_array($warningKey, $this->confirmedWarnings);
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getConfirmedWarnings(): array
    {
        return $this->confirmedWarnings;
    }

    public function setConfirmedWarnings(array $confirmedWarnings): self
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
