<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This class is 'configurable' - it has an `update()` method and a
 * constructor that accepts iterables.
 *
 * Combine this with `Configure::configure()`.
 *
 * @package karmabunny\kb
 */
interface Configurable
{

    /**
     * @param iterable $config
     * @return void
     */
    public function __construct($config);


    /**
     * @param iterable $config
     * @return void
     */
    public function update($config);
}
