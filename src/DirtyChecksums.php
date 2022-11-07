<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

namespace karmabunny\kb;

use ArrayIterator;
use Traversable;

/**
 *
 * @package karmabunny\kb
 */
class DirtyChecksums implements NotSerializable
{

    /** @var string[] [ name => sha ] */
    public $checksums = [];

    /** @var object */
    public $target;


    /**
     * Initialise this checksums store.
     *
     * This creates checksums for all public properties of the target.
     *
     * @param object $target
     */
    public function __construct(object $target)
    {
        $this->target = $target;
        $this->update($target);
    }


    /**
     * Get a checksum for this value.
     *
     * @param mixed $value
     * @return string
     */
    protected function getChecksum($value): string
    {
        return sha1(serialize($value));
    }


    /**
     * Get an iterator for the target object.
     *
     * @return Traversable
     */
    protected function getProperties(): Traversable
    {
        if ($this->target instanceof Traversable) {
            return $this->target;
        } else {
            // Iterating on a natural object from a foreign context will only
            // return public properties.
            return new ArrayIterator($this->target, ArrayIterator::STD_PROP_LIST);
        }
    }


    /**
     * Reset all checksums.
     *
     * This means all properties are DIRTY.
     *
     * @return void
     */
    public function reset()
    {
        $this->checksums = [];
    }


    /**
     * Update all checksums.
     *
     * This means all properties are CLEAN.
     *
     * @return void
     */
    public function update()
    {
        foreach ($this->getProperties() as $name => $value) {
            $this->checksums[$name] = $this->getChecksum($value);
        }
    }


    /**
     * Update the checksum for this field.
     *
     * @param string $name
     * @return void
     */
    public function updateField(string $name)
    {
        if (property_exists($this->target, $name)) {
            $this->checksums[$name] = $this->getChecksum($this->target->{$name});
        }
    }


    /**
     * Forcefully mark this property as dirty.
     *
     * @param string $name
     * @return void
     */
    public function markDirty(string $name)
    {
        if (property_exists($this->target, $name)) {
            $this->checksums[$name] = 'DIRTY';
        }
    }


    /**
     * Has this property changed since the last checksum/update?
     *
     * @param string $name
     * @return bool
     */
    public function isDirty(string $name)
    {
        // Don't know about this one. Not dirty.
        if (!property_exists($this->target, $name)) {
            return false;
        }

        // No checksum stored, so we assume it's dirty.
        if (!array_key_exists($name, $this->checksums)) {
            return true;
        }

        // Forced dirty.
        if ($this->checksums[$name] === 'DIRTY') {
            return true;
        }

        // Checksums don't match, it's dirty.
        if ($this->checksums[$name] !== $this->getChecksum($this->target->{$name})) {
            return true;
        }

        // Otherwise clean.
        return false;
    }


    /**
     * Retrieve data that has changed since the last checksum.
     *
     * @return array [name => value]
     */
    public function getAllDirty(): array
    {
        $dirty = [];

        foreach ($this->getProperties() as $name => $value) {
            if (!$this->isDirty($name)) continue;
            $dirty[$name] = $value;
        }

        return $dirty;
    }
}