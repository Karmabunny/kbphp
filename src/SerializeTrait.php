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
    protected function getSerializedProperties(): array
    {
        $array = Reflect::getProperties($this, static::$SERIALIZE);

        return array_filter($array, function($value) {

            if (is_object($value) and $value instanceof NotSerializable) {
                return false;
            }

            return true;
        });
    }


    /** @inheritdoc */
    public function serialize()
    {
        $serialized = $this->__serialize();
        if ($serialized === null) return null;
        return serialize($serialized);
    }


    /** @inheritdoc */
    public function unserialize($serialized)
    {
        $serialized = unserialize($serialized);
        $this->__unserialize($serialized);
    }


    /** @inheritdoc */
    public function __serialize()
    {
        if ($this instanceof NotSerializable) {
            return null;
        }
        else {
            return $this->getSerializedProperties();
        }
    }


    /** @inheritdoc */
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
