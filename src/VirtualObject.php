<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;

/**
 * Attach this to a property to convert it automatically to the target class.
 *
 * The object must implement the {@see AttributeVirtualTrait} and matching
 * {@see UpdateVirtualInterface} to enable this for DataObject types.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class VirtualObject extends VirtualPropertyBase
{

    /** @var class-string */
    public $class;


    /**
     *
     * @param class-string $class
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
    public function apply(object $target, $value): bool
    {
        if (is_array($value)) {
            $value = Configure::create($this->class, $value);
        }
        else if (is_object($value) and get_class($value) === $this->class) {
            $value = $value;
        }
        else if (
            // @phpstan-ignore-next-line : PHP8 only.
            ($type = $this->reflect->getType())
            and $type->allowsNull()
        ) {
            $value = null;
        }
        else {
            // Invalid.
            return false;
        }

        $this->reflect->setAccessible(true);
        $this->reflect->setValue($target, $value);

        return true;
    }

}
