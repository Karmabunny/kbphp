<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2025 Karmabunny
*/

namespace karmabunny\kb;

use InvalidArgumentException;

/**
 * Get a singleton instance for this class + config.
 *
 * @see Configure::getStaticInstance()
 * @package karmabunny\kb
 */
trait InstanceTrait
{
    /**
     * Get a singleton instance for this class + config.
     *
     * @param array $config
     * @return static
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public static function instance(array $config = [])
    {
        return Configure::getStaticInstance(static::class, $config);
    }
}
