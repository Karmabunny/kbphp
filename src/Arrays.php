<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use Generator;
use Throwable;
use Traversable;

/**
 * Array utilities.
 *
 * @package karmabunny\kb
 */
abstract class Arrays
{

    /**
     * Get the first key of an array.
     *
     * @param array $array
     * @return int|string|null
     */
    static function firstKey(array $array)
    {
        reset($array);
        return key($array);
    }


    /**
     * Get the last key of an array.
     * @param array $array
     * @return int|string|null
     */
    static function lastKey(array $array)
    {
        end($array);
        return key($array);
    }


    /**
     * First item in the array.
     *
     * @param array $array
     * @return mixed
     */
    static function first(array $array)
    {
        $key = self::firstKey($array);
        if ($key === null) return null;
        return $array[$key];
    }


    /**
     * Last item in the array.
     *
     * @param array $array
     * @return mixed
     */
    static function last(array $array)
    {
        $key = self::lastKey($array);
        if ($key === null) return null;
        return $array[$key];
    }


    /**
     * Create a reversed iterator of an array.
     *
     * Useful for _big_ arrays because array_reverse() creates a copy, whereas
     * this one does not.
     *
     * @param array|Traversable $array
     * @return Generator
     */
    static function reverse($array)
    {
        end($array);
        while (($key = key($array)) !== null)
        {
            yield $key => current($array);
            prev($array);
        }
    }


    /**
     * Fill an array with the results of a callable.
     *
     * Each index is provided as an argument.
     *
     * The $index arg is a reference and can be modified to alter the key.
     *
     * For a more straight-forward API, see filledKeys().
     *
     * @param int $size
     * @param callable $fn (&$index) => $value
     * @return array
     */
    static function fill(int $size, callable $fn)
    {
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            $key = $i;
            $value = $fn($key);
            $array[$key] = $value;
        }
        return $array;
    }


    /**
     * Fill a keyed array with the results of a callable.
     *
     * Each index is provided as an argument.
     * The callable is expected to return an array pair [key, value].
     *
     * @param int $size
     * @param callable $fn ($index) => [$key, $value]
     * @return array
     */
    static function fillKeyed(int $size, callable $fn)
    {
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            list($key, $value) = $fn($i);
            $array[$key] = $value;
        }
        return $array;
    }


    /**
     * Find a matching item.
     *
     * The callable is provided with the value FIRST and the key SECOND.
     *
     * You can write:
     *  Arrays::find($stuff, fn($item) => $item->id === 100);
     *
     * Or maybe:
     *  Array::find($stuff, fn($item, key) => $key === 12 and $item->name === 12);
     *
     * @param array $array
     * @param callable $fn ($value, $key) => bool
     * @return mixed|null
     */
    static function find(array $array, callable $fn)
    {
        foreach ($array as $key => $item) {
            if ($fn($item, $key)) return $item;
        }

        return null;
    }


    /**
     * Flat arrays, with optional key support.
     *
     * @param array $array
     * @param bool $keys
     * @return array
     */
    static function flatten(array $array, $keys = false): array
    {
        $return = [];

        if ($keys) {
            $fn = function($item, $key) use (&$return) {
                $return[$key] = $item;
            };
        }
        else {
            $fn = function($item) use (&$return) {
                $return[] = $item;
            };
        }

        array_walk_recursive($array, $fn);
        return $return;
    }


    /**
     * Make everything an array, all the way down.
     *
     * This converts any nested arrayables to arrays.
     *
     * @param Arrayable|array $array
     * @return array
     */
    static function toArray($array): array
    {
        if ($array instanceof Arrayable) {
            return $array->toArray();
        }

        foreach ($array as &$item) {
            if ($item instanceof Arrayable) {
                $item = $item->toArray();
            }
        }
        return $array;
    }


    /**
     * Is this array a numeric/non-associated array.
     *
     * Opposite of isAssociated().
     *
     * @param mixed $array
     * @return bool
     */
    static function isNumeric($array): bool
    {
        if (!is_array($array)) return false;

        foreach ($array as $key => $_) {
            if (!is_int($key)) return false;
        }
        return true;
    }


    /**
     * Is this an associated/keyed array.
     *
     * Opposite of isNumeric().
     *
     * @param mixed $array
     * @return bool
     */
    static function isAssociated($array): bool
    {
        return !self::isNumeric($array);
    }


    /**
     * Query an array.
     *
     * It's a funny concept, but quite powerful.
     *
     * For example, given an array like:
     * ```
     * [
     *    'items' => [
     *      [ 'subitem' => [ 'id' => 123, 'list' => [1,2,3] ] ],
     *      [ 'subitem' => [ 'id' => 456, 'list' => [4,5,6] ] ],
     *    ],
     *    'options' => [
     *       'host' => 'localhost',
     *       'port' => 5060,
     *    ]
     * ]
     *
     * // A query `items.subitem.id` would return:
     * => [ 123, 456 ]
     *
     * // Or `items.subitem.list`:
     * => [ [1, 2, 3], [4, 5, 6] ]
     *
     * // Perhaps `options`:
     * => [ 'host' => 'localhost', 'port' => '5060' ]
     *
     * // And `options.host`:
     * => 'localhost'
     * ```
     *
     * @param array $array
     * @return mixed
     */
    static function value($array, string $query)
    {
        // Must be an array.
        if (!is_array($array)) return null;

        // Pull apart the query, get our bit, stitch it back together.
        // e.g. 'one.two.three' becomes:
        // key => 'one'
        // query => 'two.three'
        $parts = explode('.', $query);
        $key = array_shift($parts);
        $query = implode('.', $parts);

        // No support for numeric keys.
        if (is_numeric($key)) return null;

        // Look it up.
        $value = $array[$key] ?? null;

        // Not found, quit!
        if ($value === null) return null;

        // End of the query, we found the thing!
        // At this point it doesn't matter the value type. Anything works.
        if (!strlen($query)) return $value;

        // Presented with an array, pick out the numeric bits, if any.
        if (is_array($value)) {
            $values = [];

            foreach ($value as $key => $item) {
                if (!is_numeric($key)) continue;
                if ($item === null) continue;

                $item = self::value($item, $query);
                if ($item === null) continue;

                $values[] = $item;
            }

            // Cheeky flatten for single item arrays.
            while (count($values) === 1 and is_array(@$values[0])) {
                $values = $values[0];
            }

            // Only if we got what we want, otherwise defer to the
            // recursive associated method (below).
            if (!empty($values)) {
                return $values;
            }
        }

        // Recurse on.
        // The value must be a value or associated array.
        return self::value($value, $query);
    }


    /**
     * Shorthand for putting together [key => value] maps.
     *
     * This will silently skip over invalid items:
     * - string/int/null instead of array/object
     * - invalid key types (int/string only)
     * - missing keys/names
     *
     * @param array $items
     * @param string $key
     * @param string $name
     * @param string|null $select Include a 'choose' option
     * @return array
     */
    static function createMap(array $items, string $key, string $name, string $select = null)
    {
        $map = [];

        if ($select) {
            $map[''] = $select;
        }

        foreach ($items as $item) {
            try {
                if (is_object($item)) {
                    $index = $item->$key;
                    $value = $item->$name;
                }
                else if (is_array($item)) {
                    $index = $item[$key];
                    $value = $item[$name];
                }

                $index = (string) $index;

                $map[$index] = $value;
            }
            catch (Throwable $error) {}
        }

        return $map;
    }

}
