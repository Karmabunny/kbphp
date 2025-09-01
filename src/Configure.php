<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

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
     * @param class-string|null $assert class name to verify
     * @param bool $init whether to initialize the object after creation
     * @return object
     * @throws InvalidArgumentException
     */
    public static function configure($config, ?string $assert = null, bool $init = true)
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

        // Pass-through the object.
        if (is_object($config)) {
            if ($assert and !$config instanceof $assert) {
                throw new InvalidArgumentException("{$class} must extend '{$assert}'");
            }

            if ($init and $config instanceof ConfigurableInit) {
                $config->init();
            }

            return $config;
        }

        $object = self::instance($class, $assert);
        self::update($object, $config);

        // Call the init() function, if present.
        if ($init and $object instanceof ConfigurableInit) {
            $object->init();
        }

        return $object;
    }


    /**
     * Configure all objects in an array.
     *
     * @param (array|object|string)[] $configs
     * @param class-string|null $assert
     * @param bool $init
     * @return object[]
     */
    public static function all(array $configs, ?string $assert = null, bool $init = true): array
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


    /**
     * Construct a new instance of this class.
     *
     * @template T
     * @param class-string<T> $class
     * @param class-string|class-string[] $assert
     * @return T
     * @throws InvalidArgumentException
     */
    public static function instance(string $class, $assert = null)
    {
        // Check the class exists.
        try {
            /** @var string $class */
            $reflect = new ReflectionClass($class);
        }
        catch (ReflectionException $ex) {
            throw new InvalidArgumentException("Class '{$class}' does not exist");
        }

        // Basic checks.
        if ($reflect->isAbstract()) {
            throw new InvalidArgumentException("Class '{$class}' is abstract");
        }

        // Do inheritance asserts.
        if ($assert) {
            $assert = (array) $assert;

            foreach ($assert as $chk) {
                if (!$reflect->isSubclassOf($chk)) {
                    throw new InvalidArgumentException("Class {$class} must extend '{$chk}'");
                }
            }
        }

        // Do it.
        try {
            return $reflect->newInstance();
        }
        catch (ReflectionException $ex) {
            throw new InvalidArgumentException("Class '{$class}' cannot be instantiated");
        }
    }


    /**
     * Set variables on an object.
     *
     * This will use the `Configurable` interface or falls back to
     * dynamic properties.
     *
     * Note, in PHP 8.2 you will need the `#[\AllowDynamicProperties]`
     * attribute to set dynamic properties. Even then, this behaviour
     * is deprecated.
     *
     * @param object $object
     * @param array $config
     * @return void
     */
    public static function update($object, array $config)
    {
        // Do configurable things because we can.
        if ($object instanceof Configurable) {
            $object->update($config);
        }
        // Or just the regular.
        else {
            foreach ($config as $key => $value) {
                $object->$key = $value;
            }
        }
    }


    /**
     * Instance a single object.
     *
     * @template T
     * @param class-string<T> $class
     * @param array $config
     * @return T
     */
    public static function create(string $class, array $config)
    {
        $object = self::instance($class);
        self::update($object, $config);
        return $object;
    }


    /**
     * Create lots of objects with these configs.
     *
     * @template T
     * @param class-string<T> $class
     * @param array[] $items
     * @return T[]
     */
    public static function createAll(string $class, array $items)
    {
        /** @var array $items */

        foreach ($items as &$item) {
            $item = self::create($class, $item);
        }

        unset($item);
        return $items;
    }
}
