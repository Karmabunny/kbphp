<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;
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
        return serialize($this->toArray());
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
    public function copy(array $fields = null)
    {
        $class = static::class;
        $array = $this->toArray($fields);
        return new $class($array);
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
