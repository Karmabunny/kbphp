<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use karmabunny\interfaces\ConfigurableInterface;
use karmabunny\interfaces\JobInterface;
use karmabunny\interfaces\LoggableInterface;
use karmabunny\interfaces\RulesValidatorInterface;
use karmabunny\interfaces\ValidatesInterface;

/**
 * A job. Could be a cron or a worker. You decide.
 *
 * Has built-in config, logging, validation, stats.
 *
 * @package karmabunny\kb
 */
abstract class Job implements
    JobInterface,
    ConfigurableInterface,
    LoggableInterface,
    ValidatesInterface
{
    use LoggerTrait;
    use RulesValidatorTrait;

    /** @var string */
    public $id;

    /** @var int Unix timestamp in seconds. */
    public $start;

    /** @var array */
    public $config;


    /**
     * Create and validate a job with this config.
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->update($config);
        $this->start = time();
        $this->validate();
    }


    /** @inheritdoc */
    public function update($config)
    {
        if (!is_array($config)) {
            $config = iterator_to_array($config, true);
        }

        $this->config = $config;
    }


    /** @inheritdoc */
    public function getId(): string
    {
        if (!$this->id) {
            $this->id = uniqid();
        }

        return $this->id;
    }


    /**
     *
     * @return RulesValidatorInterface
     */
    public function getValidator(): RulesValidatorInterface
    {
        return new RulesStaticValidator($this->config);
    }


    /**
     * Get the current stats.
     *
     * This is automatically printed in {@see execute()}.
     *
     * @return string[]
     */
    public function stats(): array
    {
        $seconds = time() - $this->start;
        $minutes = '';

        if ($seconds > 60) {
            $minutes = sprintf(' (%d minutes)', round($seconds / 60));
        }

        $stats = [];
        $stats[] = "Time: {$seconds} seconds" . $minutes;
        $stats[] = 'Started: ' . date('Y-m-d H:i:s', $this->start);
        return $stats;
    }


    /**
     * Shorthand for creating, validating and running a job.
     *
     * @param array $config
     * @return Job
     */
    public static function execute(array $config = [])
    {
        $class = static::class;

        $job = new $class($config);

        $job->addLogger(function($message) {
            echo Log::stringify($message), PHP_EOL;
        });

        $job->run();

        foreach ($job->stats() as $stat) {
            $job->log($stat);
        }

        return $job;
    }
}
