<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayIterator;

/**
 * Mostly just level defs.
 *
 * @package karmabunny\kb
 */
class Log
{

    /** Put your noisey stuff in here. */
    const LEVEL_DEBUG = LOG_DEBUG;

    /** Important things, but not verbose. */
    const LEVEL_INFO = LOG_INFO;

    /** Some needs to pay attention to this. */
    const LEVEL_WARNING = LOG_WARNING;

    /** No really, this is bad. */
    const LEVEL_ERROR = LOG_ERR;

    /** Shh. */
    const LEVEL_SILENT = 0;


    /**
     * Get the friendly level name.
     *
     * @param int $level
     * @return string
     */
    public static function name(int $level): string
    {
        if (!$level) return '';

        if ($level >= self::LEVEL_DEBUG) {
            return 'DEBUG';
        }
        if ($level >= self::LEVEL_INFO) {
            return 'INFO';
        }
        if ($level >= self::LEVEL_WARNING) {
            return 'WARNING';
        }
        if ($level >= self::LEVEL_ERROR) {
            return 'ERROR';
        }

        return "LEVEL({$level})";
    }


    /**
     * Get the level ID by it's name.
     *
     * @param string $name
     * @return int
     */
    public static function level(string $name): int
    {
        $name = strtoupper($name);
        switch ($name) {
            case 'DEBUG':
                return self::LEVEL_DEBUG;

            default:
            case 'INFO':
                return self::LEVEL_INFO;

            case 'WARNING':
                return self::LEVEL_WARNING;

            case 'ERROR':
                return self::LEVEL_ERROR;

            case 'SILENT':
                return self::LEVEL_SILENT;
        }
    }


    /**
     *
     * @param mixed $message
     * @param int $level
     * @param string $category
     * @param int $timestamp
     * @return void - echoes to stdout
     */
    public static function print($message, $level, $category, $timestamp)
    {
        echo self::format($message, $level, $category, $timestamp);
    }


    /**
     *
     * @param mixed $message
     * @param int $level
     * @param string $category
     * @param int $timestamp
     * @return string
     */
    public static function format($message, $level, $category, $timestamp)
    {
        $line = '';
        $line .= '[' . date('c', $timestamp) . ']';
        $line .= '[' . self::name($level) . ']';
        $line .= '[' . $category . ']';
        $line .= ' ' . self::stringify($message);
        return trim($line);
    }


    /**
     * An attempt to convert things into strings.
     *
     * @param mixed $value
     * @return string
     */
    public static function stringify($value, $indent = 0): string
    {
        // Bad hack.
        if (!$indent and is_object($value)) $indent = 2;

        $pad = str_repeat(' ', $indent);

        if ($value === null) {
            return 'NULL';
        }

        // Json'd for \n escapes and whatnot.
        if (is_scalar($value)) {
            return trim(json_encode($value, JSON_UNESCAPED_SLASHES), '"\'');
        }

        // Recurse into models, class names otherwise.
        if (is_object($value)) {
            $out = get_class($value) . PHP_EOL;

            if (!($value instanceof \Traversable)) {
                // @phpstan-ignore-next-line : docs say 'array or object'
                $value = new ArrayIterator($value);
            }

            foreach ($value as $key => $item) {
                $out .= "{$pad}{$key}:";
                $out .= (!is_array($item) or empty($item)) ? ' ' : PHP_EOL;
                $out .= self::stringify($item, $indent + 2);
                $out .= PHP_EOL;
            }

            // Also virtual fields.
            if ($value instanceof Collection) {
                foreach ($value->fields() as $key => $item) {
                    // Call it.
                    if (!is_callable($item)) continue;
                    $item = $item();

                    $out .= "{$pad}{$key}:";
                    $out .= (!is_array($item) or empty($item)) ? ' ' : PHP_EOL;
                    $out .= self::stringify($item, $indent + 2);
                    $out .= PHP_EOL;
                }
            }

            return trim($out, PHP_EOL);
        }

        // Looped and recursive.
        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }

            $out = '';
            foreach ($value as $index => $item) {
                $out .= "{$pad}[{$index}]:";
                $out .= (!is_array($item) or empty($item)) ? ' ' : PHP_EOL;
                $out .= self::stringify($item, $indent + 2);
                $out .= PHP_EOL;
            }
            return trim($out, PHP_EOL);
        }

        // Gross.
        if (is_resource($value)) {
            $value = (int) $value;
            return "resource({$value})";
        }

        // God forbid.
        return '???';
    }


    /**
     * Dump and die!
     *
     * @param mixed $thing
     * @return void
     */
    public static function dump($thing)
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('content-type: text/plain');
        echo self::stringify($thing);
        die;
    }


    /**
     * Create a logger method that writes messages to a file.
     *
     * The messages are cached in a in-memory queue to reduce disk activity.
     * Disable it with a `$cache_size = 1`.
     *
     * @param string $path Write the file here (append mode)
     * @param int $cache_size Only write to disk every 'x' messages
     * @param int[]|int $levels Filtering; only log on these levels
     * @return callable (message, level, category)
     */
    public static function createFileLogger(string $path, $cache_size = 5, $levels = null)
    {
        // A happy little closure value.
        $cache = [];

        // Flush logs on shutdown.
        register_shutdown_function(function () use (&$cache, $path) {
            foreach ($cache as $message) {
                @file_put_contents($path, $message . PHP_EOL, FILE_APPEND);
            }

            // Disable any more logging.
            $cache = null;
        });

        if (!is_array($levels)) {
            $levels = [$levels];
        }

        return function ($message, $level, $category, $timestamp)
                use (&$cache, $path, $cache_size, $levels) {

            // Disabled logging after shutdown.
            if ($cache === null) return;

            // Some filtering.
            if ($levels and !in_array($level, $levels)) return;

            $cache[] = self::format($message, $level, $category, $timestamp);

            // Chunk size reached, flush the cache.
            if (count($cache) >= $cache_size) {
                foreach ($cache as $message) {
                    @file_put_contents($path, $message . PHP_EOL, FILE_APPEND);
                }
                $cache = [];
            }
        };
    }

}
