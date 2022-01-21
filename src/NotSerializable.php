<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * When serializing with {@see SerializeTrait} child properties that implement
 * this are skipped.
 *
 * NOTE! - if the root class implements this it is _not_ skipped by it's own
 * serialize method.
 *
 * @package karmabunny\kb
 */
interface NotSerializable {}
