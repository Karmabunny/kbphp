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
        $fields = array_fill_keys(static::getProperties(), true);

        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $fields)) continue;
            $this->$key = $value;
        }

        if (method_exists($this, 'applyVirtual')) {
            call_user_func([$this, 'applyVirtual']);
        }
    }
}
