<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use Closure;
use ReflectionClass;

/**
 *
 * Usage:
 *
 * ```
 * class MyClass
 * {
 *    use HookTrait;
 *
 *    public function myMethod($arg1, $arg2)
 *    {
 *       self::_hook(__FUNCTION__);
 *       // do whatever.
 *    }
 *
 *    #[Hook('myMethod')]
 *    public function myHook()
 *    {
 *       // do something else.
 *    }
 * }
 * ```
 *
 * @package karmabunny/kb
 */
trait HooksTrait
{
    /** @var array[] [ id => Hook[] ] */
    protected static $_hooks = null;


    /**
     *
     * @param string|null $id
     * @return int
     */
    protected static function _hook(string $id = null): int
    {
        // Fetch the calling method. Is this performant? Not sure.
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $frame = $trace[1];

        // Support for calling through these helpers.
        if (
            $frame['function'] === 'call_user_func'
            or $frame['function'] === 'call_user_func_array'
        ) {
            $frame = $trace[2];
        }

        $object = $frame['object'] ?? null;

        // Populate all of the hooks for this class.
        if (!isset(static::$_hooks[static::class])) {
            static::$_hooks[static::class] = [];

            $reflect = new ReflectionClass(static::class);
            $methods = $reflect->getMethods();

            foreach ($methods as $method) {
                if ($method->isAbstract()) continue;
                if (!$method->isUserDefined()) continue;

                $method->setAccessible(true);

                // Find regular doc tags, for PHP7 support.
                $tags = Reflect::getMethodTags($method->getDocComment(), ['hook']);

                foreach ($tags['hook'] as $tag) {
                    $hook = new Hook($tag);
                    $hook->prepare($method);

                    static::$_hooks[static::class][$hook->getId()][] = $hook;
                }

                // Find PHP8 attribute hooks. These potentially have more
                // power because they can be inherited and such.
                if (PHP_VERSION_ID >= 80000) {
                    $attributes = $method->getAttributes(Hook::class);

                    foreach ($attributes as $attribute) {
                        /** @var Hook $hook */
                        $hook = $attribute->newInstance();
                        $hook->prepare($method);

                        static::$_hooks[static::class][$hook->getId()][] = $hook;
                    }
                }
            }
        }

        // Fill in the ID if not specified.
        $id = $id ?? $frame['function'] ?? null;
        $args = $frame['args'] ?? [];

        /** @var Hook[] */
        $hooks = static::$_hooks[static::class][strtolower($id)] ?? [];

        foreach ($hooks as $hook) {
            $hook->run($object, ...$args);
        }

        return count($hooks);
    }
}
