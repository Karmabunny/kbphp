<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This class has logging capabilities.
 *
 * Implementation is provided in {@see LoggerTrait}.
 *
 * @package karmabunny\kb
 */
interface Loggable {

    /**
     * Log something.
     *
     * @param mixed $message string, array, exception - whatever
     * @param int $level default: LEVEL_INFO
     * @param string|null $_category default: class name (static)
     * @param int|float|null $_timestamp default: now
     * @return void
     */
    public function log($message, ?int $level = null, ?string $_category = null, $_timestamp = null);
}
