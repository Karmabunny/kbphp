<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Some handy cache bits.
 *
 * These is only a runtime cache per object. So it's very short lived.
 *
 * It's just for when you've done some computation and you don't want store
 * it somewhere without it getting in the way. It just lives within the model
 * as it travels through your code.
 *
 * @package karmabunny\kb
 */
trait CachedHelperTrait
{

    private $_cache = [];


    /**
     * Trash this cache.
     *
     * Maybe you've mutated something. Go on. Trash it.
     *
     * Recommended placements:
     * - `__clone()`
     * - `Collection::update()`
     *
     * @return void
     */
    protected function clearCache()
    {
        $this->_cache = [];
    }


    /**
     * Get a date object version of the 'property' field.
     *
     * @param string $property
     * @return DateTimeInterface
     */
    protected function getCachedDate(string $property): DateTimeInterface
    {
        return $this->getCachedValue($property, function() use ($property) {
            return new DateTimeImmutable($this->$property);
        });
    }


    /**
     * Cache the result of a function.
     *
     * Use this with caution plz.
     *
     * @param string $id
     * @param callable $fn () => mixed
     * @return mixed function result
     */
    protected function getCachedValue(string $id, $fn)
    {
        if (!isset($this->_cache[$id])) {
            $this->_cache[$id] = $fn();
        }
        return $this->_cache[$id];
    }


    /**
     * Cache the result of a function against a hash of the input.
     *
     * This is largely identical to `getCachedValue()` but a bit more flexible.
     *
     * Your inputs _must_ be serializable.
     *
     * @param array $inputs
     * @param mixed $fn (...$inputs) => mixed
     * @return mixed
     */
    protected function getCachedResult(array $inputs, $fn)
    {
        $key = sha1(serialize($inputs));

        if (!isset($this->_cache[$key])) {
            $this->_cache[$key] = $fn(...$inputs);
        }
        return $this->_cache[$key];
    }

}