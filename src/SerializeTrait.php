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
     * @var int
     */
    protected static $SERIALIZE = ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC;


    /** @inheritdoc */
    public function serialize()
    {
        $array = [];

        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(static::$SERIALIZE);

        foreach ($properties as $property) {
            if ($property->isStatic()) continue;

            $key = $property->getName();

            $value = $this->$key;
            if (is_object($value) and $value instanceof NotSerializable) continue;

            $array[$key] = $value;
        }

        return serialize($array);
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
