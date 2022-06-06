<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

use InvalidArgumentException;

/**
 * Implements a configure() helper.
 *
 * @see Configure
 * @see Configurable
 *
 * @package karmabunny\kb
 */
trait ConfigureTrait
{
    /**
     * Configure an object.
     *
     * If 'configurable' this will use the 'update()' method. Otherwise it'll
     * set properties directly on the object.
     *
     * The config is a key-value array, for example:
     *
     * ```
     * [
     *     '\\My\\Big\\Class' => [
     *        'foo' => 'bar',
     *        'baz' => 'qux',
     *     ]
     * ]
     * ```
     *
     * Given a string the 'config' is implicitly an empty array.
     *
     * @param string|array|object $config [ class => config ]
     * @param string $assert class name to verify
     * @return object
     */
    public static function configure($config, string $assert = null)
    {
        // Mush it into a key-config pair.
        if (is_string($config)) {
            $config = [ $config => [] ];
        }

        // Pass through objects.
        if (is_object($config)) {
            $class = get_class($config);
        }
        // It's an array.
        else {
            $class = key($config);
            $config = reset($config);
        }

        // Check that we're all behaving here.
        if ($assert
            and $assert !== $class
            and !is_subclass_of($class, $assert)
        ) {
            throw new InvalidArgumentException("{$class} must extend '{$assert}'");
        }

        if (is_object($config)) {
            return $config;
        }

        // Do configurable things because we can.
        if (is_subclass_of($class, Configurable::class)) {
            $object = new $class($config);
        }
        // Or just the regular.
        else {
            $object = new $class();

            foreach ($config as $key => $value) {
                $object->$key = $value;
            }
        }

        return $object;
    }
}