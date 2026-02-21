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
 */
class Config
{

    /** @var string[] */
    public static $paths = [];


    /** @var array<string,mixed> name => [key => value] */
    protected static $overrides = [];

    /** @var array<string,array> name => config */
    protected static $cache = [];

    /** @var array<string,bool[]> name => [paths] */
    protected static $loaded = [];


    /**
     * Does this config exist?
     *
     * @param string $name
     * @return string[]
     * @throws InvalidArgumentException on an invalid config name.
     */
    public static function find(string $name)
    {
        if (preg_match('![^-_a-zA-Z0-9]!', $name)) {
            throw new InvalidArgumentException("Invalid config file '{$name}'");
        }

        $paths = [];

        foreach (self::$paths as $path) {
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
        self::apply($config, $path, $name);
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
        [$name, $subkey] = explode('.', $key, 2) + ['', null];

        $paths = self::find($name);

        if ($paths) {
            $config = self::$cache[$name] ?? [];

            foreach ($paths as $path) {
                if (isset(self::$loaded[$name][$path])) {
                    continue;
                }

                self::apply($config, $path);
                self::$loaded[$name][$path] = true;
            }

            self::$cache[$name] = $config;

            foreach (self::$overrides[$name] ?? [] as $key => $value) {
                self::querySet($config, $key, $value);
            }

            // Do a key query.
            if ($subkey !== null) {
                $config = self::query($config, $subkey);
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
        [$name, $key] = explode('.', $key, 2) + ['', null];
        self::$overrides[$name][$key] = $value;
    }


    /**
     * Delete internal caches.
     *
     * @param bool $overrides Also delete the override keys.
     * @return void
     */
    public static function reset(bool $overrides = false)
    {
        self::$cache = [];
        self::$loaded = [];

        if ($overrides) {
            self::$overrides = [];
        }
    }


    /**
     * Returns the value of a key, defined by a 'dot-noted' string, from an array.
     *
     * @param array $array array to search
     * @param string $query dot-noted string: foo.bar.baz
     * @return mixed|null
     */
    protected static function query(array $array, string $query)
    {
        if (empty($array)) {
            return NULL;
        }

        // Prepare for loop
        $query = explode('.', $query);

        if (count($query) == 2) {
            return $array[$query[0]][$query[1]] ?? null;
        }

        do {
            // Get the next key
            $key = array_shift($query);

            if (isset($array[$key])) {
                if (is_array($array[$key]) AND ! empty($query)) {
                    // Dig down to prepare the next loop
                    $array = $array[$key];
                }
                else {
                    // Requested key was found
                    return $array[$key];
                }
            }
            else {
                // Requested key is not set
                break;
            }
        }
        while (!empty($query));

        return NULL;
    }


    /**
     * Sets values in an array by using a 'dot-noted' string.
     *
     * @param array $array array to set keys in (reference)
     * @param string $query dot-noted string: foo.bar.baz
     * @param mixed $value fill value for the key
     * @return mixed
     */
    protected static function querySet(array &$array, string $query, $value = null)
    {
        // Must always be an array
        if (!is_array($array)) {
            $array = (array) $array;
        }

        if (empty($query)) {
            return $array;
        }

        // Create keys
        $query = explode('.', $query);

        // Create reference to the array
        $row =& $array;

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
            $row =& $row[$key];
        }

        // Set the value for the final key (overwrites existing values).
        $row = $value;
    }
}
