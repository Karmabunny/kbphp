<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;

/**
 * Note, this cannot be used with doc tags and is therefore PHP 8+ only.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
abstract class VirtualPropertyBase extends AttributeTag
{

    /**
     *
     * @param object $target
     * @param mixed $value
     * @return void
     */
    public abstract function apply(object $target, $value);
}
