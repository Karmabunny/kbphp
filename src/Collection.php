<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 *
 *
 * @package karmabunny/kb
 */
class Collection implements \ArrayAccess, \IteratorAggregate {

    function __construct(iterable $config = [])
    {
        $this->update($config);
    }


    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this);
    }


    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }


    public function offsetGet($offset)
    {
        return @$this->$offset ?: null;
    }


    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }


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
