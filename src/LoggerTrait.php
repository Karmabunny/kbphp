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

        $filter = [];

        $category = (array) $category;

        foreach ($category as $key => $item) {
            $invert = false;

            if (!is_numeric($key)) {
                $invert = $item === false;
                $item = $key;
            }

            if ($invert) {
                $filter['exclude'][$item] = true;
            }
            else {
                $filter['permit'][$item] = true;
            }
        }

        $this->addLogger(function($message, $_level, $_category, $_timestamp) use ($parent, $filter, $level) {

            if ($filter) {
                if (isset($filter['exclude'][$_category])) {
                    return;
                }

                if (
                    !empty($filter['permit'])
                    and !isset($filter['permit'][$_category])
                ) {
                    return;
                }
            }

            if ($level and $level < $_level) {
                return;
            }

            $parent->log($message, $_level, $_category, $_timestamp);
        });
    }


    /**
     * Log something.
     *
     * @param mixed $message
     * @param int $level default: LEVEL_INFO
     * @param string|null $category default: class name (static)
     * @param int|float|null $timestamp default: now
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
