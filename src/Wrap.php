<?php

namespace karmabunny\kb;

/**
 * This is an abomination but it's very neat.
 *
 * In the interest of reducing the millions of closures about the place and
 * making code look kind of neater.
 *
 * It's only useful for limited use-cases. Arguments and scoping variables
 * are not a thing. It's literally what is says on the box.
 *
 * It's like this:
 *
 * ```
 * // Old way
 * $arrays = array_map(function($item) {
 *     return $item->toArray();
 * }, $results);
 *
 * // Better way (php7.4)
 * $arrays = array_map(fn($item) => $item->toArray(), $results);
 *
 * // This way
 * $arrays = array_map([Wrap::class, 'toArray'], $results);
 * ```
 *
 * @package karmabunny/kb
 */
class Wrap
{
    public static function __callStatic($name, $arguments)
    {
        $item = array_shift($arguments);
        return $item->$name($arguments);
    }
}
