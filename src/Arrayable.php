<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This object can be explicitly converted to an array with a `toArray()` method.
 *
 * @package karmabunny\kb
 */
interface Arrayable
{
    /**
     * Convert this object to an array.
     *
     * @return array
     */
    function toArray(): array;
}
