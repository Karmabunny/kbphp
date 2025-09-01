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
     *
     * @param iterable $config
     * @return void
     */
    public function update($config): void
    {
        foreach ($config as $key => $item) {
            if (!property_exists($this, $key)) continue;
            $this->$key = $item;
        }

        if (method_exists($this, 'applyVirtual')) {
            call_user_func([$this, 'applyVirtual']);
        }
    }
}
