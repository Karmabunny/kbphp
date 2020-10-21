<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use JsonSerializable;
use Serializable;

/**
 *
 *
 * @package karmabunny/kb
 */
class Collection implements \ArrayAccess, \IteratorAggregate, Serializable, JsonSerializable {

    function __construct(iterable $config = [])
    {
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


    public function update(iterable $config)
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


    public function toArray()
    {
        $array = [];
        foreach ($this as $key => $item) {
            $array[$key] = $item;
        }
        return $array;
    }
}
