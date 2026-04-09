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
    public function apply(object $target, $value): bool
    {
        if (is_array($value)) {
            $items = [];

            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $items[$key] = Configure::create($this->class, $item);
                }
                else if (get_class($item) === $this->class) {
                    $items[$key] = $item;
                }
            }
        }
        else if ($this->isNullable()) {
            $items = null;
        }
        else {
            // TODO or overwrite with an empty array?
            return false;
        }

        $this->reflect->setAccessible(true);
        $this->reflect->setValue($target, $items);

        return true;
    }
}
