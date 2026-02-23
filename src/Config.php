<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;
use RuntimeException;

/**
 * A config helper.
 *
 * This supports a 'cascading' config system. Each config is able to inherit
 * and override values from the preceding config.
 *
 * This supports both `$config` and `return` syntaxes.
 *
 * ```
 * $config['key'] = $config['key'] + 1;
 * ```
 *
 * ```
 * return [ 'key' => $config['key'] + 1 ];
 * ```
 *
 * Implement the `getPaths()` method for your project.
 */
abstract class Config
{

    /** @var array<string,Config> name => instance */
    protected static $instances = [];

    /** @var array<string,mixed> name => [key => value] */
    protected $overrides = [];

    /** @var array<string,array> name => config */
    protected $cache = [];

    /** @var array<string,bool[]> name => [paths] */
    protected $loaded = [];


    /**
     * Get or create an instance of this config.
     *
     * @param bool $refresh
     * @return static
     */
    public static function instance(bool $refresh = false)
    {
        $instance = self::$instances[static::class] ?? null;

        if ($instance === null or $refresh) {
            $instance = new static();
        }

        self::$instances[static::class] = $instance;
        return $instance;
    }


    /**
     * Get the paths to load configs from.
     *
     * @return array
     */
    public abstract function getPaths(): array;


    /**
     * Does this config exist?
     *
     * @param string $name
     * @return string[]
     * @throws InvalidArgumentException on an invalid config name.
     */
    public function find(string $name)
    {
        if (preg_match('![^-_a-zA-Z0-9]!', $name)) {
            throw new InvalidArgumentException("Invalid config file '{$name}'");
        }

        $paths = [];

        foreach ($this->getPaths() as $path) {
            $path = rtrim($path, '/') . "/{$name}.php";
            if (is_readable($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }


    /**
     * Load and merge configs.
     *
     * @param string $path
     * @param string $name
     * @return bool
     * @throws RuntimeException if a recursive config file is detected.
     */
    public static function apply(array &$config, string $path, string $name = 'config'): bool
    {
        static $__recurse;

        if ($path === $__recurse) {
            throw new RuntimeException('Recursive config file: ' . basename($path, '.php'));
        }

        if (!is_readable($path)) {
            return false;
        }

        $output = (static function($__path, $__name, $__config) use (&$__recurse) {
            try {
                $__recurse = $__path;
                $$__name = $__config;

                $alt = @include $__path;

                if (is_array($alt)) {
                    $$__name = array_merge($$__name, $alt);
                }

                return $$__name;
            }
            finally {
                $__recurse = null;
            }
        })($path, $name, $config);

        if (!is_array($output)) {
            return false;
        }

        $config = $output;
        return true;
    }


    /**
     * Load just one config file.
     *
     * This does not apply overrides, nor uses `$paths` or caches.
     *
     * @param string $path
     * @param string $name
     * @return array
     * @throws RuntimeException if a recursive config file is detected.
     */
    public static function load(string $path, string $name = 'config'): array
    {
        $config = [];
        static::apply($config, $path, $name);
        return $config;
    }


    /**
     * Fetch a config value.
     *
     * @param string $key a query string like 'foo.bar.baz'
     * @param bool $required
     * @return mixed|null
     * @throws InvalidArgumentException When a config doesn't exist.
     * @throws RuntimeException if a recursive config file is detected.
     */
    public static function get(string $key, $required = true)
    {
        $instance = static::instance();
        [$name, $subkey] = explode('.', $key, 2) + ['', null];

        $paths = $instance->find($name);

        if ($paths) {
            $config = $instance->cache[$name] ?? [];

            foreach ($paths as $path) {
                if (isset($instance->loaded[$name][$path])) {
                    continue;
                }

                static::apply($config, $path);
                $instance->loaded[$name][$path] = true;
            }

            $instance->cache[$name] = $config;

            foreach ($instance->overrides[$name] ?? [] as $key => $value) {
                static::querySet($config, $key, $value);
            }

            // Do a key query.
            if ($subkey !== null) {
                $config = static::query($config, $subkey);
            }
        }

        // Got one.
        if (isset($config)) {
            return $config;
        }

        if ($required) {
            throw new InvalidArgumentException("Config not found: '{$key}'");
        }

        return null;
    }


    /**
     * Set a config override value.
     *
     * @param string $key a query string like 'foo.bar.baz'
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value)
    {
        $instance = static::instance();
        [$name, $key] = explode('.', $key, 2) + ['', null];
        $instance->overrides[$name][$key] = $value;
    }


    /**
     * Returns the value of a key, defined by a 'dot-noted' string, from an array.
     *
     * @param array $array array to search
     * @param string $query dot-noted string: foo.bar.baz
     * @return mixed|null
     */
    public static function query(array $array, string $query)
    {
        if (empty($array)) {
            return null;
        }

        // Prepare for loop
        $query = explode('.', $query);

        if (count($query) == 2) {
            return $array[$query[0]][$query[1]] ?? null;
        }

        do {
            // Get the next key
            $key = array_shift($query);

            // Requested key is not set
            if (!isset($array[$key])) {
                break;
            }

            // Dig down to prepare the next loop
            if (is_array($array[$key]) and !empty($query)) {
                $array = $array[$key];
                continue;
            }

            // Requested key was found
            return $array[$key];
        }
        while (!empty($query));

        return null;
    }


    /**
     * Sets values in an array by using a 'dot-noted' string.
     *
     * @param array $array array to set keys in (reference)
     * @param string $query dot-noted string: foo.bar.baz
     * @param mixed $value fill value for the key
     * @return mixed
     */
    public static function querySet(array &$array, string $query, $value = null)
    {
        if (empty($query)) {
            return $array;
        }

        // Create keys
        $query = explode('.', $query);

        // Create reference to the array
        $row = &$array;

        for ($i = 0, $end = count($query) - 1; $i <= $end; $i++) {
            // Get the current key
            $key = $query[$i];

            if (!isset($row[$key])) {
                if (isset($query[$i + 1])) {
                    // Make the value an array
                    $row[$key] = [];
                }
                else {
                    // Add the fill key
                    $row[$key] = $value;
                }
            }
            else if (isset($query[$i + 1])) {
                // Make the value an array
                $row[$key] = (array) $row[$key];
            }

            // Go down a level, creating a new row reference
            $row = &$row[$key];
        }

        // Set the value for the final key (overwrites existing values).
        $row = $value;
    }
}
