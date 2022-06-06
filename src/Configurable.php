<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This class is 'configurable' - it has an `update()` method accepts iterables.
 *
 * Combine this with `Configure::configure()`.
 *
 * @package karmabunny\kb
 */
interface Configurable
{
    /**
     * Update the object with a new configuration.
     *
     * @param iterable $config
     * @return void
     */
    public function update($config);
}
