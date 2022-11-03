<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

namespace karmabunny\kb;


/**
 * Track dirty property values.
 *
 * Dirty properties can only be 'public'.
 *
 * This trait is intended to be combined with existing data/collection classes.
 *
 * @package karmabunny\kb
 */
trait DirtyPropertiesTrait
{

    /** @var DirtyChecksums|null */
    private $_checksums;


    /**
     * Get a checksums store for this object.
     *
     * Call this ASAP in your object lifecycle, i.e. the constructor.
     *
     * Note this only tracks public properties.
     *
     * @return DirtyChecksums
     */
    public function getChecksums(): DirtyChecksums
    {
        if ($this->_checksums === null) {
            $this->_checksums = new DirtyChecksums($this);
        }

        return $this->_checksums;
    }

}
