<?php

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;

/**
 * Cast an array to an object.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CastObject extends Cast
{

    /**
     *
     * @param class-string $class
     * @return void
     */
    public function __construct(public string $class)
    {
    }


    /** @inheritdoc */
    public function build(mixed $value): ?object
    {
        if (!is_array($value)) {
            if ($this->isNullable()) {
                return null;
            }

            throw new InvalidArgumentException("Expected value for {$this->class} got " . get_debug_type($value));
        }

        return Configure::create($this->class, $value);
    }
}
