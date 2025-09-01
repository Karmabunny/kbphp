<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;
use karmabunny\interfaces\LogSinkInterface;

/**
 * Hook logging handlers into a class.
 *
 * @package karmabunny\kb
 */
trait LoggerTrait {

    /** @var callable[] */
    private $loggers = [];


    public function clearLoggers()
    {
        $this->loggers = [];
    }


    /**
     * Register a logger.
     *
     * Logs can be filtered by level and category.
     *
     * ```
     * // Only permit log levels higher than this:
     * // (excludes INFO, DEBUG, etc)
     * $loggable->addLogger($logger, Log::LEVEL_WARNING);
     *
     * // Only permit this category:
     * $loggable->addLogger($logger, null, 'progress');
     *
     * // Only permit these categories:
     * $loggable->addLogger($logger, null, ['log.request', 'log.response']);
     *
     * // Exclude these categories:
     * $loggable->addLogger($logger, null, ['stats' => false, 'meta' => false]);
     * ```
     *
     * @param callable|LogSinkInterface $logger (message, level, category, timestamp)
     * @param string|array|null $category filter by category
     * @param int|null $level filter by level
     * @return int
     */
    public function addLogger($logger, ?int $level = null, $category = null): int
    {
        if (
            $logger === $this
            or (is_array($logger) and $logger[0] ?? false === $this)
        ) {
            throw new InvalidArgumentException('Cannot attach to self');
        }

        if ($level !== null or $category !== null) {
            $logger = Log::filter($logger, $level, $category);
        }

        $index = count($this->loggers);
        $this->loggers[] = $logger;
        return $index;
    }


    /**
     * Forward any logs from this loggable to a parent loggable.
     *
     * @deprecated Use addLogger() instead
     * @param callable|LogSinkInterface $parent
     * @param string|array|null $category filter by category
     * @param int|null $level filter by level
     * @return void
     */
    public function attach($parent, ?int $level = null, $category = null)
    {
        if ($level !== null or $category !== null) {
            $logger = Log::filter($parent, $level, $category);
        }

        $this->addLogger($logger);
    }


    /**
     * Log something.
     *
     * @param mixed $message
     * @param int $level default: LEVEL_INFO
     * @param string|null $_category default: class name (static)
     * @param int|float|null $_timestamp default: now
     * @return void
     */
    public function log($message, ?int $level = null, ?string $_category = null, $_timestamp = null): void
    {
        if ($level === null) $level = Log::LEVEL_INFO;
        if ($_category === null) $_category = static::class;
        if ($_timestamp === null) $_timestamp = microtime(true);

        foreach ($this->loggers as $logger) {
            $logger($message, $level, $_category, $_timestamp);
        }
    }
}
