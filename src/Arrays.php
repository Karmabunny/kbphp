<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Array utilities.
 *
 * @package karmabunny\kb
 */
abstract class Arrays
{

    /**
     * First item in the array.
     *
     * @param array $array
     * @return mixed
     */
    static function first(array $array)
    {
        foreach ($array as $item) {
            return $item;
        }
        return null;
    }


    /**
     * Last item in the array.
     *
     * @param array $array
     * @return mixed
     */
    static function last(array $array)
    {
        $item = null;
        foreach ($array as $item);
        return $item;
    }


    /**
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
     * @param mixed<[] $array
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
}
