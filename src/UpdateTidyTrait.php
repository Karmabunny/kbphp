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
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        $fields = static::getPropertyTypes();

        foreach ($config as $key => $value) {
            $type = $fields[$key] ?? null;
            if (!$type) continue;

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

        if ($this instanceof UpdateVirtualInterface) {
            $this->setVirtual($config);
        }
    }
}
