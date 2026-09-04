<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;
use ReflectionMethod;

/**
 * Cast values with a custom method.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CastMethod extends Cast
{

    /**
     *
     * @param string $method
     * @return void
     */
    public function __construct(public string $method)
    {
    }


    /** @inheritdoc */
    public function build(mixed $value): mixed
    {
        $reflect = new ReflectionMethod($this->target, $this->method);

        $value = $reflect->invoke($this->target, $value);

        if ($value === null and !$this->isNullable()) {
            throw new InvalidArgumentException("Cannot set null on {$this->property}");
        }

        return $value;
    }
}
