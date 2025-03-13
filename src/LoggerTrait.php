<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;

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
     * @param callable $logger (message, level, category, timestamp)
     * @return int
     */
    public function addLogger(callable $logger)
    {
        $index = count($this->loggers);
        $this->loggers[] = $logger;
        return $index;
    }


    /**
     * Forward any logs from this loggable to a parent loggable.
     *
     * Logs can be filtered by level and category.
     *
     * ```
     * // Only permit log levels higher than this:
     * // (excludes INFO, DEBUG, etc)
     * $loggable->attach($parent, Log::LEVEL_WARNING);
     *
     * // Only permit this category:
     * $loggable->attach($parent, null, 'progress');
     *
     * // Only permit these categories:
     * $loggable->attach($parent, null, ['log.request', 'log.response']);
     *
     * // Exclude these categories:
     * $loggable->attach($parent, null, ['stats' => false, 'meta' => false]);
     * ```
     *
     * @param Loggable $parent
     * @param string|array|null $category filter by category
     * @param int|null $level filter by level
     * @return void
     */
    public function attach(Loggable $parent, ?int $level = null, $category = null)
    {
        if ($parent === $this) {
            throw new InvalidArgumentException('Cannot attach to self');
        }

        $logger = Log::filter([$parent, 'log'], $level, $category);
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
    public function log($message, ?int $level = null, ?string $_category = null, $_timestamp = null)
    {
        if ($level === null) $level = Log::LEVEL_INFO;
        if ($_category === null) $_category = static::class;
        if ($_timestamp === null) $_timestamp = microtime(true);

        foreach ($this->loggers as $logger) {
            $logger($message, $level, $_category, $_timestamp);
        }
    }
}
