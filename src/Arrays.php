<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use Generator;
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
     * @param int $size
     * @param callable $fn ($index) => $value
     * @return array
     */
    static function fill(int $size, callable $fn)
    {
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            $array[] = $fn($i);
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
     * @param array $array
     * @return bool
     */
    static function isNumeric(array $array): bool
    {
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
     * @param array $array
     * @return bool
     */
    static function isAssociated(array $array): bool
    {
        return !self::isNumeric($array);
    }



    /**
     * Query an array.
     *
     * It's a funny concept, but quite powerful.
     *
     * For example, given an array like:
     * [
     *    [ 'subitem' => 123 ],
     *    [ 'subitem' => 456 ],
     * ]
     * A query `subitem.id` would return:
     * [ 123, 456 ]
     *
     *
     * @param array|ArrayAccess $array
     * @return mixed
     */
    static function getValue($array, string $query)
    {
        // Pull apart the query, get our bit, stitch it back together.
        $parts = explode('.', $query);
        $key = array_shift($parts);
        $query = implode('.', $parts);

        $value = $array[$key] ?? null;

        // Not found, quit!
        if ($value === null) return null;

        // End of the query, we found the thing!
        if (empty($query)) return $value;

        // Get each valid item as an array, recursive of course.
        if (is_iterable($value)) {
            $values = [];
            foreach ($value as $item) {
                $values[] = self::getValue($item, $query);
            }
            return $values;
        }

        // Recurse on.
        return self::getValue($value, $query);
    }

}
