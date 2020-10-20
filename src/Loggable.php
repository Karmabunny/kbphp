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
 * @package karmabunny/kb
 */
interface Loggable {

    /**
     * Log something.
     *
     * @param string|\Exception $message
     * @param int $level default: LEVEL_INFO
     * @return void
     */
    public function log($message, int $level = null, string $_category = null, int $_timestamp = null);
}
