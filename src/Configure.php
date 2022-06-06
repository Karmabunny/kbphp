<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

/**
 * Utility for configuring things.
 *
 * @package karmabunny\kb
 */
class Configure
{
    use ConfigureTrait;


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
