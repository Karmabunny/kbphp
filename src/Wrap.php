<?php

namespace karmabunny\kb;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

/**
 * Neater closures!
 *
 * This is now less of an abomination.
 *
 * In the interest of reducing the millions of closures about the place and
 * making code look kind of neater.
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
 * $arrays = array_map(Wrap::method('toArray'), $results);
 *
 * // And then maybe
 * $filtered = array_map(Wrap::item('active'), $arrays);
 *
 * // Or even
 * $filtered = array_filter($results, Wrap::property('active'));
 *
 * // Lastly
 * $mapped = array_map(Wrap::construct(Thing::class), $results);
 * ```
 *
 * @package karmabunny/kb
 */
class Wrap
{

    /**
     * Wrap a constructor.
     *
     * ```
     * fn(...$args) => new $name(...$args);
     * ```
     *
     * @param string $name a class name
     * @return callable (...$args) => object
     * @throws InvalidArgumentException
     */
    public static function construct(string $name)
    {
        // Validate, but also autoload things.
        if (!class_exists($name, true)) {
            throw new InvalidArgumentException("Class {$name} does not exist");
        }

        return function (...$args) use ($name) {
            return new $name(...$args);
        };
    }


    /**
     * Wrap instanceof.
     *
     * ```
     * fn($item) => $item instanceof $name;
     * ```
     *
     * @param string $name a class name
     * @return callable ($item) => bool
     * @throws InvalidArgumentException
     */
    public static function instanceOf(string $name)
    {
        return function ($item) use ($name) {
            return (
                $name === get_class($item) or
                is_subclass_of($item, $name, false)
            );
        };
    }


    /**
     * Create a wrapper that gets te property of an object.
     *
     * ```
     * fn($item) => $item->$name;
     * ```
     *
     * @param string $name
     * @return callable (object) => mixed
     */
    public static function property(string $name)
    {
        return function ($item) use ($name) {
            if (!is_object($item)) return null;
            return $item->$name;
        };
    }


    /**
     * Create a wrapper that calls the method of an object.
     *
     * ```
     * fn($item) => $item->$name(...$args);
     * ```
     *
     * @param string $name
     * @param array $args
     * @return callable (object) => mixed
     */
    public static function method(string $name, ...$args)
    {
        return function ($item) use ($name, $args) {
            if (!is_object($item)) return null;
            return $item->$name(...$args);
        };
    }


    /**
     * Create a wrapper that gets the key of an array.
     *
     * ```
     * fn($item) => $item[$key];
     * ```
     *
     * @param string|int $key
     * @return callable (array) => mixed
     */
    public static function item($key)
    {
        return function ($item) use ($key) {
            if (!(
                is_array($item) or
                $item instanceof ArrayAccess
            )) return null;

            return $item[$key] ?? null;
        };
    }
}
