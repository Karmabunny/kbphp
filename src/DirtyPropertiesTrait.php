<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;


/**
 * Track dirty property values.
 *
 * This trait is intended to be combined with existing data/collection classes.
 *
 * @package karmabunny\kb
 */
trait DirtyPropertiesTrait
{

    /** @var string[]|null [name => sha1] */
    private $_checksums = null;


    /**
     * Forcefully mark this property as dirty.
     *
     * Note, this will only mark the property if the internal checksums have
     * been initialised.
     *
     * @param string $name
     * @return void
     */
    public function markPropertyDirty(string $name)
    {
        if (
            $this->_checksums !== null
            and property_exists($this, $name)
        ) {
            $this->_checksums[$name] = 'DIRTY';
        }
    }


    /**
     * Is this property dirty?
     *
     * @param string $name
     * @return bool
     */
    public function isPropertyDirty(string $name): bool
    {
        // Not yet initailised.
        if ($this->_checksums === null) {
            return false;
        }

        if (!property_exists($this, $name)) {
            return false;
        }

        // No checksum stored, so we assume it's dirty.
        if (!array_key_exists($name, $this->_checksums)) {
            return true;
        }

        // Forced dirty.
        if ($this->_checksums[$name] === 'DIRTY') {
            return true;
        }

        // Checksums don't match, it's dirty.
        if ($this->_checksums[$name] !== sha1(json_encode($this->{$name}))) {
            return true;
        }

        // Otherwise clean.
        return false;
    }


    /**
     * Record checksums for this data.
     *
     * Checksums should not be used until they are initialised with this method.
     *
     * @param bool $initialize
     * @return void
     */
    public function updateDirtyProperties($initialize = true)
    {
        if (!$initialize and $this->_checksums === null) {
            return;
        }

        // Initialize.
        $this->_checksums = [];

        foreach ($this as $name => $_value) {
            $this->updateDirtyProperty($name);
        }
    }


    /**
     * Record checksum for this property.
     *
     * @param string $name
     * @return void
     */
    public function updateDirtyProperty(string $name)
    {
        if (
            $this->_checksums !== null
            and property_exists($this, $name)
        ) {
            $this->_checksums[$name] = sha1(json_encode($this->{$name}));
        }
    }


    /**
     * Retrieve data that has changed since the last checksum.
     *
     * Note, if the dirty checksums are not initialised this then returns
     * all properties.
     *
     * @return array [name => value]
     */
    public function getDirtyProperties(): array
    {
        if ($this->_checksums === null) {
            return iterator_to_array($this);
        }

        $data = [];

        foreach ($this as $name => $value) {
            if (!$this->isPropertyDirty($name)) continue;
            $data[$name] = $value;
        }

        return $data;
    }

}
