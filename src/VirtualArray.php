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
class VirtualArray extends VirtualPropertyBase
{

    /** @var class-string */
    public $class;

    /**
     * Consume invalid values.
     *
     * Invalid values are set to null (if able) or otherwise an empty array.
     *
     * @var bool
     */
    public $squashInvalid;

    /**
     * Convert array items to objects of the target class.
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
        if (is_iterable($value) and !is_array($value)) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            $items = [];

            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $items[$key] = Configure::create($this->class, $item);
                }
                else if (
                    is_object($item)
                    and is_a($item, $this->class, false)
                ) {
                    $items[$key] = $item;
                }
            }

            $this->reflect->setValue($target, $items);
            return true;
        }

        // @phpstan-ignore-next-line : PHP8 only.
        $type = $this->reflect->getType();
        $nullable = (!$type or $type->allowsNull());

        // Null is passed though.
        if ($value === null and $nullable) {
            $this->reflect->setValue($target, null);
            return true;
        }

        // Consume invalid values.
        // Invalid values are set to empty arrays - if untyped.
        // This is different to the behaviour in VirtualObject.
        if ($this->squashInvalid) {
            $value = ($nullable and $type) ? null : [];
            $this->reflect->setValue($target, $value);
            return true;
        }

        return false;
    }
}
