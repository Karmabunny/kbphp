<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Error;
use InvalidArgumentException;
use ReflectionException;
use ReflectionProperty;

/**
 * Attach this to a property to tie it to a local method.
 *
 * The object must implement the {@see AttributeVirtualTrait} and use
 * the `applyVirtual()` helper to invoke the virtual methods.
 *
 * @package karmabunny\kb
 */
class VirtualProperty extends VirtualPropertyBase
{

    /**
     * @var string
     */
    public $method;


    /**
     *
     * @param string $method
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $method)
    {
        $this->method = $method;
    }


    /** @inheritdoc */
    public function apply(object $target, $value)
    {
        if (!($this->reflect instanceof ReflectionProperty)) {
            throw new Error('VirtualProperty must be parsed from an object');
        }

        try {
            $class = $this->reflect->getDeclaringClass();
            $method = $class->getMethod($this->method);
            $method->invoke($target, $value);
        }
        catch (ReflectionException $ex) {
            throw new Error("Virtual method not found: {$this->method}");
        }
    }
}
