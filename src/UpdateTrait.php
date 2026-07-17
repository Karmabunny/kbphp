<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use karmabunny\interfaces\ConfigurableInterface;

/**
 * This implements basic `update()` behaviour for an object.
 *
 * Only fields that are defined as properties will be set. Unknown fields
 * are silently ignored.
 *
 * To raise errors on unknown fields {@see UpdateStrictTrait}.
 *
 * @mixin ConfigurableInterface
 * @package karmabunny\kb
 */
trait UpdateTrait
{
    /**
     *
     * @param iterable $config
     * @return void
     */
    public function update(iterable $config): void
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
