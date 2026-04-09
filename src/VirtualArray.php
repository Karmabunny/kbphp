<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use Error;
use InvalidArgumentException;
use ReflectionProperty;

/**
 * Attach this to a property to convert it automatically to the target class.
 *
 * The object must implement the {@see AttributeVirtualTrait} and use
 * the `applyVirtual()` helper to invoke the virtual methods.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class VirtualArray extends VirtualPropertyBase
{

    /**
     * @var string
     */
    public $class;

    /**
     *
     * @param string $method
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Target class does not exist: {$class}");
        }

        $this->class = $class;
    }


    /** @inheritdoc */
    public function apply(object $target, mixed $value)
    {
        if (!($this->reflect instanceof ReflectionProperty)) {
            throw new Error('VirtualProperty must be parsed from an object');
        }

        $this->reflect->setAccessible(true);

        $items = [];

        foreach ($value as $key => $item) {
            $item = Configure::create($this->class, $item);
            $items[$key] = $item;
        }

        $this->reflect->setValue($target, $items);
    }
}
