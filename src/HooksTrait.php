<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

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
 *       $this->_hook(__FUNCTION__, 'first');
 *       // do whatever.
 *    }
 *
 *    #[Hook('myMethod')]
 *    protected function myHook($arg1)
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
    /** @var Hook[][] [ id => Hook[] ] */
    protected $_hooks = null;


    /**
     * Call a hook.
     *
     * This will initialise the hooks if they haven't already.
     *
     * @param string $id
     * @return int
     */
    protected function _hook(string $id, ...$args): int
    {
        // Populate all of the hooks for this class.
        if ($this->_hooks === null) {
            $this->_hooks = [];

            $hooks = Hook::parse($this);

            foreach ($hooks as $hook) {
                $this->_hooks[$hook->id][] = $hook;
            }
        }

        // Hook IDs are case insensitive.
        $id = strtolower($id);

        /** @var Hook[] $hooks */
        $hooks = $this->_hooks[$id] ?? [];

        // Fire off relevant hooks.
        foreach ($hooks as $hook) {
            $hook->run($this, ...$args);
        }

        return count($hooks);
    }
}
