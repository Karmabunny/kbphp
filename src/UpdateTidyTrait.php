<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This modifies the behaviour of a DataObject/Collection so that only
 * properties defined in the class are updated.
 *
 * The default implementation will create _new_ fields that aren't typed. This
 * trait will only update fields that are explicitly defined. Unknown fields
 * are silently ignored.
 *
 * For a more aggressive approach {@see UpdateStrictTrait}.
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
    }
}
