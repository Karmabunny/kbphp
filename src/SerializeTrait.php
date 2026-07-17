<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionProperty;

/**
 * Implements a PHP serialiser.
 *
 * Important notes!:
 *  - by default this will only serialize public + protected properties.
 *  - Child object that implement `NotSerializable` are not included.
 *
 * You can extend, or restrict, serialization per class by overriding
 * the `getSerializedProperties()` method.
 */
trait SerializeTrait
{

    /**
     * Get an array of properties to serialize.
     *
     * Override this to modify the behaviour of the serializer.
     *
     * By default, this returns properties that are:
     * - public
     * - not private or protected
     * - not static
     * - not implementing `NotSerializable`
     *
     * @return mixed[] [string => value]
     */
    protected function getSerializedProperties(): array
    {
        $properties = Reflect::getProperties($this, ReflectionProperty::IS_PUBLIC);

        $properties = array_filter($properties, function($value) {
            return !(is_object($value) and $value instanceof NotSerializable);
        });

        return $properties;
    }


    /** @inheritdoc */
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
    public function __unserialize(array $serialized): void
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
