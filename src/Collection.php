<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use Serializable;
use Traversable;

/**
 * A collection is a defined model for an object.
 *
 * This is primarily to encourage stronger typing for blobs of data moving
 * within a system.
 *
 * Collection can be created from arrays with `new MyCollection([...])`. After
 * which they are safe to move around.
 *
 * Being objects, you can safely create functionality that relies on the data
 * inside them with methods or inheritance.
 *
 * Some useful utilities for collections:
 * - DocValidator
 * - RulesValidator
 * - NotSerializable
 *
 * Collections also provide:
 * - array access
 * - serialization
 * - iteration
 *
 * @package karmabunny\kb
 */
abstract class Collection extends DataObject implements
        ArrayAccess,
        IteratorAggregate,
        Serializable,
        JsonSerializable,
        Arrayable,
        Copyable
{

    use ArrayAccessTrait;
    use ArrayableTrait;


    /** @inheritdoc */
    public function serialize()
    {
        $array = [];

        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

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
        $this->update(unserialize($serialized));
    }


    /** @inheritdoc */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


    /** @inheritdoc */
    public function getIterator(): Traversable
    {
        // @phpstan-ignore-next-line : docs say 'array or object'
        return new ArrayIterator($this);
    }


    /**
     * @deprecated Use clone instead.
     * @return static
     */
    public function copy()
    {
        $class = static::class;
        return new $class($this);
    }
}
