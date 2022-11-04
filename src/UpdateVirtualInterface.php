<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This modifies the behaviour of a DataObject/Collection for updating complex
 * properties, such as arrays and objects.
 *
 * @package karmabunny\kb
 */
interface UpdateVirtualInterface
{

    /**
     * Apply virtual properties.
     *
     * Recommended placements:
     *  - `update()`
     *  - `__construct()`
     *
     * @param iterable $config
     * @return void
     */
    public function setVirtual($config);
}
