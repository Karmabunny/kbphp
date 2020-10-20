<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Hook logging handlers into a class.
 *
 * @package karmabunny/kb
 */
trait LoggerTrait {

    /** @var callable[] */
    private $loggers = [];


    /**
     * Register a logger.
     *
     * @param callable $logger (message, level, category, timestamp)
     * @return int
     */
    public function addLogger(callable $logger, bool $init = true)
    {
        // Log something immediately so - if it breaks - it breaks early.
        if ($init) {
            $logger('Registered logger', Log::LEVEL_DEBUG, static::class, time());
        }

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
        $this->addLogger([$parent, 'log'], false);
    }


    /**
     * Log something.
     *
     * @param string|\Exception $message
     * @param int $level default: LEVEL_INFO
     * @return void
     */
    public function log($message, int $level = null, string $_category = null, int $_timestamp = null)
    {
        if ($level === null) $level = Log::LEVEL_INFO;
        if ($_category === null) $_category = static::class;
        if ($_timestamp === null) $_timestamp = time();

        foreach ($this->loggers as $logger) {
            $logger($message, $level, $_category, $_timestamp);
        }
    }
}
