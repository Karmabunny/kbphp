<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionClass;
use ReflectionProperty;

/**
 * Implements a PHP serialiser.
 *
 * Important notes!:
 *  - by default this will only serialize public + protected properties.
 *  - Child object that implement `NotSerializable` are not included.
 *
 * You can extend, or restrict, serialization per class with `$SERIALIZE`.
 */
trait SerializeTrait
{
    /**
     * Binary-OR of property types for serialisation.
     *
     * @deprecated use getSerializedProperties()
     *
     * @var int
     */
    protected static $SERIALIZE = ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC;


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
    protected function getSerializedProperties()
    {
        $array = [];

        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(static::$SERIALIZE);

        foreach ($properties as $property) {
            if ($property->isStatic()) continue;

            // Fix private/protected access.
            $property->setAccessible(true);

            // We need to use getValue() so to bypass any __get() magic.
            $key = $property->getName();
            $value = $property->getValue($this);

            if (is_object($value) and $value instanceof NotSerializable) continue;

            $array[$key] = $value;
        }

        return $array;
    }


    /** @inheritdoc */
    public function serialize()
    {
        return serialize($this->getSerializedProperties());
    }


    /** @inheritdoc */
    public function unserialize($serialized)
    {
        $result = unserialize($serialized);

        if ($this instanceof DataObject) {
            $this->update($result);
        }
        else {
            foreach ($result as $key => $item) {
                $this->$key = $item;
            }
        }
    }
}
