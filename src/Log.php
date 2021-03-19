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
abstract class Log {

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
     * An attempt to convert things into strings.
     *
     * @param mixed $thing
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
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        // Recurse into models, class names otherwise.
        if (is_object($value)) {
            $out = get_class($value) . PHP_EOL;

            if (!is_iterable($value)) {
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
        die(self::stringify($thing));
    }
}
