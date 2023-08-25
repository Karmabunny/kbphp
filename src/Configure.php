<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

use InvalidArgumentException;

/**
 * Utility for configuring things.
 *
 * @package karmabunny\kb
 */
class Configure
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
     * @param string|null $assert class name to verify
     * @param bool $init whether to initialize the object after creation
     * @return object
     * @throws InvalidArgumentException
     */
    public static function configure($config, string $assert = null, bool $init = true)
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

        // Pass-through the object.
        if (is_object($config)) {
            if ($init and is_subclass_of($config, ConfigurableInit::class)) {
                $config->init();
            }
            return $config;
        }

        // Do configurable things because we can.
        else if (is_subclass_of($class, Configurable::class)) {
            $object = new $class();
            $object->update($config);
        }
        // Or just the regular.
        else {
            $object = new $class();

            foreach ($config as $key => $value) {
                $object->$key = $value;
            }
        }

        // Call the init() function, if present.
        if ($init and is_subclass_of($object, ConfigurableInit::class)) {
            $object->init();
        }

        return $object;
    }


    /**
     * Configure all objects in an array.
     *
     * @param (array|object|string)[] $configs
     * @param string|null $assert
     * @param bool $init
     * @return object[]
     */
    public static function all(array $configs, string $assert = null, bool $init = true): array
    {
        $objects = [];

        // Create all objects, without init.
        foreach ($configs as $config) {
            $objects[] = self::configure($config, $assert, false);
        }

        // Now initialize each of them.
        if ($init) {
            self::initAll($objects);
        }

        return $objects;
    }


    /**
     * Initialize all objects in an array.
     *
     * This only calls `init()` on objects that implement `ConfigurableInit`.
     *
     * Optionally provide 'force' to call with a `method_exists` check. This
     * provides compatibility with external code that doesn't implement these
     * configurable interfaces.
     *
     * @param object[] $objects
     * @param bool $force always init
     * @return void
     */
    public static function initAll(array $objects, $force = false)
    {
        foreach ($objects as $object) {
            if (
                ($object instanceof ConfigurableInit)
                or ($force and method_exists($object, 'init'))
            ) {
                $object->init();
            }
        }
    }
}
