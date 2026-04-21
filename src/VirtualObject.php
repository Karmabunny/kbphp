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
     * Consume invalid values.
     *
     * Invalid values are set to null (if able) or otherwise an empty object.
     *
     * @var bool
     */
    public $squashInvalid;


    /**
     * Convert arrays to objects.
     *
     * @param class-string $class
     * @param bool $squashInvalid
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $class, bool $squashInvalid = false)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Target class does not exist: {$class}");
        }

        $this->class = $class;
        $this->squashInvalid = $squashInvalid;
    }


    /** @inheritdoc */
    public function apply(object $target, $value): bool
    {
        // Already valid.
        if (
            is_object($value)
            and is_a($value, $this->class, false)
        ) {
            $this->reflect->setValue($target, $value);
            return true;
        }

        // Convert array to object.
        if (is_array($value)) {
            $value = Configure::create($this->class, $value);
            $this->reflect->setValue($target, $value);
            return true;
        }

        // @phpstan-ignore-next-line : PHP8 only.
        $type = $this->reflect->getType();
        $nullable = (!$type or $type->allowsNull());

        if (empty($value) and $nullable) {
            $this->reflect->setValue($target, null);
            return true;
        }

        if ($this->squashInvalid) {
            $value = $nullable ? null : Configure::instance($this->class);
            $this->reflect->setValue($target, $value);
            return true;
        }

        // Invalid.
        return false;
    }

}
