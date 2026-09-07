<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Cast values with a custom method.
 *
 * Usage is 3 forms:
 *
 * 1. 'methodName' - call a method on the target object
 * 2. '\functionName' - call a global function
 * 3. ['classname', 'methodName'] - call a static method on a class
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CastMethod extends Cast
{

    /**
     *
     * @param string|array $method
     * @return void
     */
    public function __construct(public string|array $method)
    {
    }


    /** @inheritdoc */
    public function build(mixed $value): mixed
    {
        // Foreign static methods.
        if (is_array($this->method)) {
            [$class, $method] = $this->method;
            $reflect = new ReflectionMethod($class, $method);
            $value = $reflect->invoke(null, $value);
        }
        // Foreign static methods (hidden string form).
        else if (str_contains($this->method, '::')) {
            $reflect = new ReflectionMethod($this->method);
            $value = $reflect->invoke(null, $value);
        }
        // Global functions.
        else if (str_starts_with($this->method, '\\')) {
            $reflect = new ReflectionFunction($this->method);
            $value = $reflect->invoke($value);
        }
        // Local instance methods.
        else {
            $reflect = new ReflectionMethod($this->target, $this->method);
            $value = $reflect->invoke($this->target, $value);
        }

        if ($value === null and !$this->isNullable()) {
            throw new InvalidArgumentException("Cannot set null on {$this->property}");
        }

        return $value;
    }
}
