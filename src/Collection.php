<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use Serializable;

/**
 *
 *
 * @package karmabunny/kb
 */
class Collection implements
        ArrayAccess,
        IteratorAggregate,
        Serializable,
        JsonSerializable,
        Arrayable,
        Copyable
{

    /**
     * Add field names here to prevent them from being serialized.
     *
     * That or implement the {@see NotSerializable} interface.
     *
     * @var array
     */
    protected static $NO_SERIALIZE = [];


    function __construct(iterable $config = [])
    {
        if (!is_array($config)) {
            $config = iterator_to_array($config);
        }
        $this->update($config);
    }


    /** @inheritdoc */
    public function serialize(): string
    {
        $array = [];

        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->isStatic()) continue;

            $key = $property->getName();
            if (in_array($key, static::$NO_SERIALIZE)) continue;

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
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this);
    }


    /** @inheritdoc */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }


    /** @inheritdoc */
    public function offsetGet($offset)
    {
        return @$this->$offset ?: null;
    }


    /** @inheritdoc */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }


    /** @inheritdoc */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }


    public function update(array $config)
    {
        foreach ($config as $key => $item) {
            $this->$key = $item;
        }
    }


    /** @return static */
    public function copy()
    {
        $class = static::class;
        return new $class($this);
    }


    public function toArray(array $fields = null): array
    {
        $array = [];
        foreach ($this as $key => $item) {
            // Filtering.
            if (!empty($fields) and !in_array($key, $fields)) {
                continue;
            }

            // Shallow convert array containing arrayables.
            if (is_array($item)) {
                $array[$key] = array_map(function($item) {
                    if ($item instanceof Arrayable) {
                        return $item->toArray();
                    }
                    return $item;
                }, $item);
                continue;
            }

            // Convert arrayables.
            if ($item instanceof Arrayable) {
                $array[$key] = $item->toArray();
                continue;
            }

            $array[$key] = $item;
        }
        return $array;
    }
}
