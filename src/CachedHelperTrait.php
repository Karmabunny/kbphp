<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use DateTimeImmutable;
use DateTimeInterface;
use Traversable;

/**
 * Some handy cache bits.
 *
 * These is only a runtime cache per object. So it's very short lived.
 *
 * It's just for when you've done some computation and you don't want store
 * it somewhere without it getting in the way. It just lives within the model
 * as it travels through your code.
 *
 * Provided you're using the typical serialization provided by SerializeTrait,
 * the internal cache array should not be stored at any point.
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
     * Provide the `key` parameter to selectively delete a cached item.
     *
     * Recommended placements:
     * - `__clone()`
     * - `DataObject::update()`
     *
     * @param string|null $key
     * @return void
     */
    protected function clearCache(?string $key = null)
    {
        if ($key) {
            unset($this->_cache[$key]);
        }
        else {
            $this->_cache = [];
        }
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
            return new DateTimeImmutable((string) $this->$property);
        });
    }


    /**
     * Cache an iterable result as an array.
     *
     * More useful than you might think. This takes anything you yield and
     * dumps it in a cached array, and also returns it.
     *
     * Example:
     * ```
     * function getItems(): array
     * {
     *     return $this->getCachedIterable('items', function() {
     *         yield 'key1' => 'one';
     *         yield 'key2' => 'two';
     *     });
     * }
     * ```
     *
     * @param string $id
     * @param callable|iterable $fn () => iterable
     * @return array
     */
    protected function getCachedIterable(string $id, $fn): array
    {
        if (!array_key_exists($id, $this->_cache)) {
            $result = ($fn instanceof Traversable) ? $fn : $fn();
            $this->_cache[$id] = iterator_to_array($result);
        }
        return $this->_cache[$id];
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
        if (!array_key_exists($id, $this->_cache)) {
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

        if (!array_key_exists($key, $this->_cache)) {
            $this->_cache[$key] = $fn(...$inputs);
        }
        return $this->_cache[$key];
    }

}
