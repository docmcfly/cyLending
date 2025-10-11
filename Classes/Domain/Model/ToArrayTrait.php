<?php
declare(strict_types=1);

namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
trait ToArrayTrait
{
    public function toArray(int $maxLevel = 10): array
    {
        if ($maxLevel === 0) {
            return [];
        }
        $properties = get_object_vars($this);
        foreach ($properties as $propertyName => $propertyValue) {
            if (str_starts_with($propertyName, '_')) {
                unset($properties[$propertyName]);
            } else if (
                is_object($propertyValue)
                && $this->useThisTrait($propertyValue)
            ) {
                $properties[$propertyName] = $propertyValue->toArray($maxLevel - 1);
            } else if (is_array($propertyValue)) {
                foreach ($propertyValue as $key => $arrayValue) {
                    if (
                        is_object($arrayValue)
                        && $this->useThisTrait($arrayValue)
                    ) {
                        $propertyValue[$key] = $arrayValue->toArray($maxLevel - 1);
                    }
                }
            }
        }
        if ($this instanceof AbstractEntity) {
            $properties['uid'] = $this->getUid();
        }
        return $properties;
    }



    private function useThisTrait($classOrObject): bool
    {
        return in_array(ToArrayTrait::class, class_uses($classOrObject, true), true);
    }
}