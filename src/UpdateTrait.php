<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This implements basic `update()` behaviour for an object.
 *
 * Only fields that are defined as properties will be set. Unknown fields
 * are silently ignored.
 *
 * To raise errors on unknown fields {@see UpdateStrictTrait}.
 *
 * @package karmabunny\kb
 */
trait UpdateTrait
{
    /**
     * Update the object.
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        $virtual = [];

        // Apply virtual properties.
        if ($this instanceof UpdateVirtualInterface) {
            $virtual = $this->setVirtual($config);
            $virtual = array_fill_keys($virtual, true);
        }

        foreach ($config as $key => $item) {
            if (!property_exists($this, $key)) continue;
            if (isset($virtual[$key])) continue;
            $this->$key = $item;
        }
    }
}
