<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;
use ReflectionException;
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
        try {
            $reflect = new ReflectionMethod($this->target, $this->method);

            if (!$reflect->isStatic() or !$reflect->isPublic()) {
                throw new InvalidArgumentException("Method {$this->method} is not static or public");
            }
        }
        catch (ReflectionException $error) {
            throw new InvalidArgumentException("Method {$this->method} not found", 0, $error);
        }

        $value = ($this->target)::{$this->method}($value);

        if ($value === null and !$this->isNullable()) {
            throw new InvalidArgumentException("Cannot set null on {$this->property}");
        }

        return $value;
    }
}
