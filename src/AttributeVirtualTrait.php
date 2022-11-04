<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionProperty;

/**
 * This modifies the behaviour of a DataObject/Collection for updating complex
 * properties, such as arrays and objects.
 *
 * Attach a {@see VirtualProperty} to a property with the target method name.
 *
 * Note, these attributes cannot be used with doc tags and is therefore
 * PHP 8+ only.
 *
 * Example:
 * ```
 * #[VirtualObject(User::class)]
 * public User $user;
 * ```
 *
 * @package karmabunny\kb
 */
trait AttributeVirtualTrait
{

    /**
     * Apply the virtual converters to the all properties.
     *
     * Recommended placements:
     *  - `__clone()`
     *  - `update()`
     *
     * @return string[]
     */
    protected function applyVirtual(): array
    {
        $virtuals = VirtualPropertyBase::parse($this);
        $properties = [];

        foreach ($virtuals as $virtual) {
            if (!($virtual->reflect instanceof ReflectionProperty)) {
                continue;
            }

            $virtual->reflect->setAccessible(true);

            $name = $virtual->reflect->getName();
            $value = $virtual->reflect->getValue($this);

            if ($value === null) continue;

            // Prevent applying things twice.
            // Use the first one and ignore the rest.
            if (!isset($properties[$name])) {
                $properties[$name] = true;

                $virtual->apply($this, $value);
            }
        }

        return array_keys($properties);
    }
}
