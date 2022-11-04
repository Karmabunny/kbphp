<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

namespace karmabunny\kb;


/**
 * Track dirty property values.
 *
 * @package karmabunny\kb
 */
interface DirtyObjectInterface
{

    /**
     * Get a checksums store for this object.
     *
     * Call this ASAP in your object lifecycle, i.e. the constructor.
     *
     * @return DirtyChecksums
     */
    public function getChecksums(): DirtyChecksums;

}
