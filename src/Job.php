<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;


/**
 *
 *
 *
 * @package karmabunny\kb
 */
abstract class Job
{

    /** @var int */
    public $start;

    /** @var array */
    public $config;


    public function __construct(array $config)
    {
        $this->config = $config;
        $this->start = time();
    }


    abstract public function run();


    public function stats()
    {
        $seconds = time() - $this->start;
        $this->log('Time:', $seconds, 'seconds');
    }


    public function log(...$args)
    {
        echo implode(' ', $args), PHP_EOL;
    }


    /**
     *
     * @param array $config
     * @return void
     */
    public static function execute(array $config = [])
    {
        /** @var Job */
        $class = static::class;
        $job = new $class($config);
        $job->run();
        $job->stats();
    }
}
