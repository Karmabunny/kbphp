<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionProperty;
use ReturnTypeWillChange;

/**
 * Implements a PHP serialiser.
 *
 * Important notes!:
 *  - by default this will only serialize public + protected properties.
 *  - Child object that implement `NotSerializable` are not included.
 *
 * You can extend, or restrict, serialization per class by overriding
 * the `getSerializedProperties()` method.
 *
 * This provides compatibility with the `Serializable` interface. Modern
 * PHP (7.4+) will use the magic `__serialize()/__unserialize()` methods if
 * available, but still prefers the "Serializable" methods.
 *
 * When overriding these methods, just override the magic ones and let this
 * trait wrap them to comply with the `Serializable` interface.
 */
trait SerializeTrait
{

    /**
     * Get an array of properties to serialize.
     *
     * Override this to modify the behaviour of the serializer.
     *
     * By default, this returns properties that are:
     * - public or protected
     * - not private
     * - not static
     * - not implementing `NotSerializable`
     *
     * @return mixed[] [string => value]
     */
    protected function getSerializedProperties(): array
    {
        $properties = Reflect::getProperties($this, ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

        $properties = array_filter($properties, function($value) {
            return !(is_object($value) and $value instanceof NotSerializable);
        });

        return $properties;
    }


    /** @inheritdoc */
    #[ReturnTypeWillChange]
    public function serialize()
    {
        $serialized = $this->__serialize();
        return serialize($serialized);
    }


    /** @inheritdoc */
    #[ReturnTypeWillChange]
    public function unserialize($serialized)
    {
        $serialized = unserialize($serialized);
        $this->__unserialize($serialized);
    }


    /** @inheritdoc */
    // phpcs:ignore
    public function __serialize(): array
    {
        if ($this instanceof NotSerializable) {
            return [];
        }
        else {
            return $this->getSerializedProperties();
        }
    }


    /** @inheritdoc */
    #[ReturnTypeWillChange]
    // phpcs:ignore
    public function __unserialize(array $serialized)
    {
        if ($this instanceof DataObject) {
            $this->update($serialized);
        }
        else {
            foreach ($serialized as $key => $item) {
                $this->$key = $item;
            }
        }
    }
}
