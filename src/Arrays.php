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
    static function array_first(array $array)
    {
        return reset($array);
    }


    /**
     * Last item in the array.
     *
     * @param array $array
     * @return mixed
     */
    static function array_last(array $array)
    {
        $item = null;
        foreach ($array as $item);
        return $item;
    }
}
