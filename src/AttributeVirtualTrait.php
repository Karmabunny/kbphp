<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;


/**
 * This modifies the behaviour of a DataObject/Collection for updating complex
 * properties, such as arrays and objects.
 *
 * Attach virtual attributes to properties to enable automatic conversion.
 *
 * - {@see VirtualProperty}
 * - {@see VirtualObject}
 * - {@see VirtualArray}
 *
 * Notes:
 * - 'automatic' is a stretch, implementors must call `setVirtual()`, ideally
 *   alongside any build `update()` methods.
 * - these attributes cannot be used with doc tags and are therefore PHP 8+ only.
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
     * @param array $config
     * @return string[]
     */
    public function setVirtual(array $config): array
    {
        $virtuals = VirtualPropertyBase::parse(static::class);

        $seen = [];
        $updated = [];

        foreach ($virtuals as $virtual) {
            $name = $virtual->getName();
            $value = $config[$name] ?? null;

            if ($value === null) continue;

            // Prevent applying things twice.
            // Use the first one and ignore the rest.
            if (!isset($seen[$name])) {
                $seen[$name] = true;
                $ok = $virtual->apply($this, $value);
                if ($ok) $updated[] = $name;
            }
        }

        return $updated;
    }
}
