<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This class extends 'configurable' to include a `init()` method that performs
 * initialization of properties. Consider it like a constructor but doesn't
 * enforce arguments.
 *
 * Other benefits include having a set of objects all created before calling
 * `init()` therefore permitting them to know of each other's presence.
 *
 * Combine this with `Configure::configure()`.
 *
 * @package karmabunny\kb
 */
interface ConfigurableInit extends Configurable
{
    /**
     * Initialize the object.
     *
     * @return void
     */
    public function init();
}
