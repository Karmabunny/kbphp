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
 * Attach a {@see VirtualProperty} or {@see VirtualObject} to a property to
 * enable automatic conversion of these properties.
 *
 * Notes:
 * - 'automatic' is a stretch, implementors must call `setVirtual()`, ideally
 *   alongside any build `update()` methods.
 * - these attributes cannot be used with doc tags and is therefore PHP 8+ only.
 *
 * Example:
 * ```
 * #[VirtualObject(User::class)]
 * public User $user;
 * ```
 *
 * Implement the {@see UpdateVirtualInterface} to enable this for DataObject types.
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
     * @param iterable $config
     * @return string[]
     */
    protected function setVirtual($config)
    {
        $virtuals = VirtualPropertyBase::parse($this);
        $properties = [];

        foreach ($virtuals as $virtual) {
            if (!($virtual->reflect instanceof ReflectionProperty)) {
                continue;
            }

            $name = $virtual->reflect->getName();
            $value = $config[$name] ?? null;

            if ($value === null) continue;

            // Prevent applying things twice.
            // Use the first one and ignore the rest.
            if (!isset($properties[$name])) {
                $properties[$name] = true;

                $virtual->reflect->setAccessible(true);
                $virtual->apply($this, $value);
            }
        }
    }
}
