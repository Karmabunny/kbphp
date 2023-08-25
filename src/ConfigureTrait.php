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
     * @param class-string|null $assert class name to verify
     * @param bool $init whether to initialize the object after creation
     * @return object
     * @throws InvalidArgumentException
     */
    public static function configure($config, string $assert = null, bool $init = true)
    {
        return Configure::configure($config, $assert, $init);
    }
}
