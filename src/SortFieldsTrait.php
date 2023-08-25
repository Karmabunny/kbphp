<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

/**
 * A simple sorter for object.
 *
 * The 'mode' parameter for compare() and {@see Arrays::createSort} can be any
 * field name or virtual field from an {@see ArrayableFields} object.
 *
 * The 'default' mode is provided by a `getSortKey()`.
 *
 * All comparisons are done with the spaceship operator.
 *
 * @see Sortable
 * @see Arrays::sort
 *
 * @package karmabunny\kb
 */
trait SortFieldsTrait
{

    /**
     * A default sort key for this object.
     *
     * When given the 'default' mode the sorter will use this. _HOWEVER_ the
     * default mode can be changed for each 'Sortable' class. See below.
     *
     * Use caution if you plan to change this within child classes. Don't go
     * changing this often if you want consistent sorts.
     *
     * The comparison is performed by the spaceship operator. Meaning numeric
     * string will sort naturally as numbers, not as alphabetically.
     *
     * @return string
     */
    public abstract function getSortKey(): string;


    /**
     * Compare this object to another.
     *
     * @param mixed $other
     * @param string $mode
     * @return int
     */
    public function compare($other, $mode = 'default'): int
    {
        if ($other instanceof static) {
            // Our default sort key.
            if ($mode === 'default') {
                return $this->getSortKey() <=> $other->getSortKey();
            }

            // How about a virtual field?
            if ($this instanceof ArrayableFields) {
                $a = $this->fields()[$mode] ?? null;
                $b = $other->fields()[$mode] ?? null;

                if (is_callable($a) and is_callable($b)) {
                    return $a() <=> $b();
                }
            }

            // Just regular fields.
            if (property_exists($this, $mode)) {
                return $this->$mode <=> $other->$mode;
            }
        }

        return -1;
    }
}
