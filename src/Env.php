<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Environment configuration helper.
 *
 * Load environment configs from the system or a file.
 *
 * @package karmabunny\kb
 */
class Env
{
    /** Pattern for matching file env files. */
    const RE = '/^([^=#]+)=(.*)/';

    const DEV = 'dev';

    const QA = 'qa';

    const TEST = 'test';

    const PROD = 'prod';

    const ENV = [
        self::DEV,
        self::QA,
        self::TEST,
        self::PROD,
    ];

    /** The key name to determine the current environment mode. */
    static $ENV_NAME = 'SITES_ENVIRONMENT';

    /** The default environment, default 'DEV'. */
    static $DEFAULT = self::DEV;

    /** @var string[] */
    static $config;


    /**
     * Configure the config.
     *
     * - Set the default environment with 'DEFAULT'
     * - Set the environment lookup name with 'ENV_NAME'
     *
     * @param array $config (ENV_NAME, DEFAULT)
     * @return void
     */
    public static function config(array $config)
    {
        self::$ENV_NAME = $config['ENV_NAME'] ?? self::$ENV_NAME;
        self::$DEFAULT = $config['DEFAULT'] ?? self::$DEFAULT;
    }


    /**
     * Load the config from the system environment.
     *
     * @param bool $clean Remove variables once we've read them.
     * @return string[] [name => value]
     */
    public static function loadFromSystem($clean = false): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        self::$config = [];

        foreach ($_SERVER as $key => $value) {
            $value = getenv($key);
            if ($value === false) continue;

            static::$config[$key] = $value;

            if ($clean) {
                $_ENV[$key] = null;
                $_SERVER[$key] = null;
                putenv($key);
            }
        }

        return self::$config;
    }


    /**
     * Load the config from a file (Recommended).
     *
     * @param string $path
     * @return string[] [name => value]
     */
    public static function loadFromFile(string $path): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        self::$config = [];

        $file = @fopen($path, 'r');

        while ($file and !feof($file)) {
            $line = fgets($file);

            $matches = [];
            if (!preg_match(self::RE, $line, $matches)) continue;

            $key = trim($matches[1]);
            if (!$key) continue;

            $value = trim(trim($matches[2]), '\'"');
            static::$config[$key] = $value;
        }

        fclose($file);
        return self::$config;
    }


    /**
     * Get the current config or load it from the system environment
     * if not already.
     *
     * It's recommended to first load the environment from a file in your
     * bootstrap.
     *
     * @return string[] [name => value]
     */
    protected static function load(): array
    {
        if (self::$config !== null) return self::$config;
        return self::loadFromSystem();
    }


    /**
     * Get a variable.
     *
     * Note, This will load variables from the system if not already populated.
     *
     * @param string $key
     * @return string|null
     */
    public static function get(string $key)
    {
        $config = self::load();
        return $config[$key] ?? null;
    }


    /**
     * Create a config from a set of environment variables.
     *
     * This is essential a shorthand mapping between environment variables
     * and some esoteric config of your own.
     *
     * There are 3 methods.
     *
     * ```
     * // 1. Provide a list of keys to get a subset config.
     * Env::getConfig([
     *    'DB_HOST',
     *    'DB_USER',
     * ]);
     * // => [ 'DB_HOST' => 'abc', 'DB_USER' => 'def']
     *
     * // 2. You can rename the keys like this.
     * Env::getConfig([
     *    'host' => 'DB_HOST',
     *    'user' => 'DB_USER',
     * ]);
     * // => [ 'host' => 'abc', 'user' => 'def']
     *
     * // 3. You can specify defaults like this.
     * Env::getConfig([
     *    'host' => [null, 'localhost'],
     *    'user' => ['DB_USER', 'test'],
     * ]);
     * // => [ 'host' => 'localhost', 'user' => 'def']
     * ```
     *
     * Note, This will load variables from the system if not already populated.
     *
     * @param string[]|null $keys
     * @return string[] [name => value]
     */
    public static function getConfig(array $keys = null): array
    {
        $config = self::load();
        if ($keys === null) return $config;

        $out = [];

        foreach ($keys as $key => $target) {
            $default = null;

            if (is_array($target)) {
                $default = $target[1] ?? null;
                $target = $target[0];
            }

            if (is_numeric($key)) {
                $key = $target;
            }

            if (!$target[0]) {
                $out[$key] = $default;
            }
            else {
                $out[$key] = $config[$target] ?? $default;
            }
        }

        return $out;
    }


    /**
     * Is this app inside a docker image?
     *
     * @return bool
     */
    public static function isDocker(): bool
    {
        static $env;

        if (!$env) {
            $env = @file_get_contents('/proc/1/cgroup');
        }

        return $env and stripos($env, 'docker') !== false;
    }


    /**
     * What is the current environment mode?
     *
     * This will normalize the environment name so 1-to-1 comparisons between
     * the `Env::ENV` variables will always work.
     *
     * @return string
     */
    public static function environment(): string
    {
        if (defined('PHPUNIT')) return self::TEST;

        $actual = self::get(self::$ENV_NAME);
        if (!$actual) return self::$DEFAULT;

        // Loosely match the environment names.
        foreach (self::ENV as $expected) {
            if (stripos($actual, $expected) === 0) return $expected;
        }

        return self::$DEFAULT;
    }


    /**
     * Is this app in an expected environment?
     *
     * @param string $expected
     * @return bool
     */
    public static function is(string $expected): bool
    {
        $env = self::environment();
        return stripos($env, $expected) === 0;
    }


    /**
     * Is this app in production mode?
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::is(self::PROD);
    }


    /**
     * Is this app in QA (quality assurance) mode?
     *
     * @return bool
     */
    public static function isQA(): bool
    {
        return self::is(self::QA);
    }


    /**
     * A shorthand method for returning the appropriate value for a config.
     *
     * For example:
     * ```
     * Env::env([
     *     'prod' => 'secret',
     *     'dev' => 'testing',
     * ]);
     * ```
     *
     * If a matching environment key doesn't exist, it'll use the default
     * environment (`Env::$DEFAULT`, typically 'dev').
     *
     * If the default env is also not present, it uses the first config item.
     *
     * @param array $config
     * @return string|null
     */
    public static function env(array $config)
    {
        $env = self::environment();
        return $config[$env] ?? $config[self::$DEFAULT] ?? reset($config) ?? null;
    }
}
