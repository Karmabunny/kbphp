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
     *
     * @param iterable $config
     */
    function __construct($config = [])
    {
        // This makes things not break. Something about references.
        if (!is_array($config)) {
            $config = iterator_to_array($config);
        }
        $this->update($config);
    }


    /** @inheritdoc */
    public function serialize(): string
    {
        $array = [];

        // Turns out ArrayIterator skips static and protected properties. Cool.
        foreach ($this as $key => $value) {
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
        return new ArrayIterator($this);
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


    /**
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
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


    /**
     * These are virtual fields for use in toArray().
     *
     * Define callbacks in here for extra things that you might want
     * included in the array version of this collection.
     *
     * @example
     *   return [
     *       'virtual_thing' => [$this, 'getMyVirtualThing'],
     *       'inline_thing' => function() {
     *            return 'hey look: ' . time();
     *       },
     *   ];
     *
     * @return callable[]
     */
    public function fields(): array
    {
        return [];
    }


    public function toArray(array $fields = null): array
    {
        $array = [];
        foreach ($this as $key => $item) {
            // Skip unset/null properties.
            // Ideally, we would still include explicitly set 'null' values.
            // Buuuut that's a PHP 7.4 feature.
            if (!isset($this->$key)) continue;

            // Filtering.
            if (!empty($fields) and !in_array($key, $fields)) {
                continue;
            }

            // Recursively convert arrayables.
            if (is_array($item) or $item instanceof Arrayable) {
                $item = Arrays::toArray($item);
            }

            $array[$key] = $item;
        }

        // Virtual fields.
        foreach ($this->fields() as $key => $item) {
            // Filtering.
            if (!empty($fields) and !in_array($key, $fields)) {
                continue;
            }

            // Call it.
            if (is_callable($item)) {
                $item = $item();
            }

            // Recursively convert arrayables.
            if (is_array($item) or $item instanceof Arrayable) {
                $item = Arrays::toArray($item);
            }

            $array[$key] = $item;
        }

        return $array;
    }
}
