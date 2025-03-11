<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

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
     * @param Loggable $parent
     * @return void
     */
    public function attach(Loggable $parent)
    {
        $this->addLogger([$parent, 'log']);
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
