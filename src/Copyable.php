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
 * @package karmabunny\kb
 */
interface Copyable
{
    /** @return static */
    function copy();
}
