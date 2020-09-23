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
    public function addLogger(callable $logger)
    {
        $index = count($this->loggers);
        $this->loggers[] = $logger;

        // Log something immediately so - if it breaks - it breaks early.
        $logger('Registered logger', Log::LEVEL_DEBUG, self::class, time());

        return $index;
    }


    /**
     * Log something.
     *
     * Optionally, provide a level and category.
     *
     * @param string|\Exception $message
     * @param string $category default: self::class
     * @param int $level default: LEVEL_INFO
     * @return void
     */
    private function log($message, int $level = null, string $category = null)
    {
        if ($level === null) $level = Log::LEVEL_INFO;
        if ($category === null) $category = self::class;

        foreach ($this->loggers as $logger) {
            $logger($message, $level, $category, time());
        }
    }
}
