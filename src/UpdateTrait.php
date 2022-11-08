<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This implements basic `update()` behaviour for an object.
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
            if (isset($virtual[$key])) continue;
            $this->$key = $item;
        }
    }
}
