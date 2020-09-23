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
     * Register a logger.
     *
     * @param callable $logger (message, level, category, timestamp)
     * @return int
     */
    public function addLogger(callable $logger);

}
