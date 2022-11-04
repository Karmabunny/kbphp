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
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        foreach ($config as $key => $item) {
            $this->$key = $item;
        }

        if (method_exists($this, '_hook')) {
            call_user_func([$this, '_hook'], __FUNCTION__, $config);
        }
    }
}
