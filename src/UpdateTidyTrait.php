<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This is functional the same as {@see UpdateTrait} only it uses the
 * `getProperties()` helper to determine which fields belong to the class.
 *
 * To raise errors on unknown fields {@see UpdateStrictTrait}.
 *
 * @package karmabunny\kb
 */
trait UpdateTidyTrait
{
    use PropertiesTrait;

    /**
     * Update the object.
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        if (!is_array($config)) {
            $config = iterator_to_array($config);
        }

        $fields = static::getPropertyTypes();

        $virtual = [];

        // Apply virtual properties.
        if ($this instanceof UpdateVirtualInterface) {
            $virtual = $this->setVirtual($config);
            $virtual = array_fill_keys($virtual, true);
        }

        foreach ($config as $key => $value) {
            // Skip virtual fields.
            if (isset($virtual[$key])) continue;

            // Skip missing properties.
            $type = $fields[$key] ?? null;
            if (!$type) continue;

            // Skip invalid types.
            // Only occurs on PHP 7.4+.
            if (
                is_object($value)
                and class_exists($type)
                and !is_a($value, $type)
            ) {
                continue;
            }

            $this->$key = $value;
        }

        // Backwards compatibility.
        if (
            !$this instanceof UpdateVirtualInterface
            and method_exists($this, 'applyVirtual')
        ) {
            $this->applyVirtual($config);
        }
    }
}
