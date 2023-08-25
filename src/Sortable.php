<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

/**
 * A sortable object.
 *
 * Example:
 *
 * ```
 * $unsorted = [sortable, sortable, sortable];
 *
 * // Shorthand.
 * $sorted = Arrays::sorted($unsorted);
 *
 * // PHP methods.
 * $compare = Arrays::createSort();
 * uasort($unsorted, $compare);
 *
 * // Custom (assumes all items are Sortable).
 * $compare = function($a, $b) {
 *     return $a->compare($b);
 * };
 * ```
 *
 * @see Arrays::createSort
 * @see Arrays::sort
 *
 * @package karmabunny\kb
 */
interface Sortable
{

    /**
     * Compare this object to another.
     *
     * Use this with {@see Arrays::sort} or write your own comparator.
     * Ultimately this always ends up in `usort/uasort`.
     *
     * The return type should be the same as the spaceship operator, that is:
     *
     * - `-1 $this < $other`  - first
     * - `0  $this == $other` - same
     * - `1  $this > $other`  - last
     *
     * If not comparable it's best to return `-1` to move the 'other' lower
     * down the list.
     *
     * Tip, combine comparisons like so:
     *
     * ```
     * return $this->group <=> $other->group ?: $this->name <=> $this->name;
     * ```
     *
     * @param mixed $other
     * @param string $mode
     * @return int
     */
    public function compare($other, $mode = 'default'): int;
}
