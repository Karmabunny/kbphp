<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use Error;
use InvalidArgumentException;
use ReflectionException;

/**
 * Attach this to a property to tie it to a local method.
 *
 * The object must implement the {@see AttributeVirtualTrait} and matching
 * {@see UpdateVirtualInterface} to enable this for DataObject types.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
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
    public function apply(object $target, $value): bool
    {
        try {
            $class = $this->reflect->getDeclaringClass();
            $method = $class->getMethod($this->method);
            $method->invoke($target, $value);
        }
        catch (ReflectionException $ex) {
            throw new Error("Virtual method not found: {$this->method}");
        }

        return true;
    }
}
