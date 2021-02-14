<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This object can be copied.
 *
 * Not quite the same as `clone $object;`. Not actually sure why you would
 * want this instead.
 *
 * @deprecated Just use clone.
 * @package karmabunny\kb
 */
interface Copyable
{
    /**
     * @deprecated
     * @return static
     */
    function copy();
}
