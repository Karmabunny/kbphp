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
     * @param int|null $level filter by level
     * @param string|array|null $category filter by category
     * @return int
     */
    public function addLogger(callable|LogSinkInterface $logger, ?int $level = null, string|array|null $category = null): int
    {
        if (
            $logger === $this
            or (is_array($logger) and ($logger[0] ?? false) === $this)
        ) {
            throw new InvalidArgumentException('Cannot attach to self');
        }

        if ($level !== null or $category !== null) {
            $logger = Log::filter($logger, $level, $category);
        }
        else if ($logger instanceof LogSinkInterface) {
            $logger = [$logger, 'log'];
        }

        $index = count($this->loggers);
        $this->loggers[] = $logger;
        return $index;
    }


    /**
     * Forward any logs from this loggable to a parent loggable.
     *
     * @deprecated Use addLogger() instead
     * @param callable|LogSinkInterface $logger
     * @param int|null $level filter by level
     * @param string|array|null $category filter by category
     * @return void
     */
    public function attach(callable|LogSinkInterface $logger, ?int $level = null, string|array|null $category = null): void
    {
        $this->addLogger($logger, $level, $category);
    }


    /**
     * Log something.
     *
     * @param mixed $message
     * @param int $level default: LEVEL_INFO
     * @param string|null $category default: class name (static)
     * @param float|null $timestamp default: now
     * @return void
     */
    public function log(mixed $message, ?int $level = null, ?string $category = null, ?float $timestamp = null): void
    {
        if ($level === null) $level = Log::LEVEL_INFO;
        if ($category === null) $category = static::class;
        if ($timestamp === null) $timestamp = microtime(true);

        foreach ($this->loggers as $logger) {
            $logger($message, $level, $category, $timestamp);
        }
    }
}
