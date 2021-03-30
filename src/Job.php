<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * A job. Could be a cron or a worker. You decide.
 *
 * Has built-in config, logging, validation, stats.
 *
 * @package karmabunny\kb
 */
abstract class Job implements Loggable
{
    use LoggerTrait;

    /** @var int Unix timestamp in seconds. */
    public $start;

    /** @var array */
    public $config;


    /**
     * Create and validate a job with this config.
     *
     * @param array $config
     * @return void
     * @throws ValidationException
     */
    public function __construct(array $config)
    {
        $this->start = time();
        $this->config = $config;

        $valid = new RulesValidator($this->config, $this->rules());
        if (!$valid->validate()) {
            throw (new ValidationException)
                ->addErrors($valid->getErrors());
        }
    }


    /**
     * Run the job.
     *
     * Put your job code in here.
     *
     * @return void
     */
    public abstract function run();


    /**
     * Validate your config, if you like.
     *
     * @see RulesValidator
     * @return array
     */
    public abstract function rules(): array;


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
        $stats = [];
        $stats[] = "Time: {$seconds} seconds";
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

        /** @var Job $job */
        $job = new $class($config);

        $job->addLogger(function($message) {
            echo $message, PHP_EOL;
        });

        $job->run();

        foreach ($job->stats() as $stat) {
            $job->log($stat);
        }

        return $job;
    }
}
