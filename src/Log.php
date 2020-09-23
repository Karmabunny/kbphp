<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Traversable;

/**
 * Mostly just level defs.
 *
 * @package karmabunny\kb
 */
abstract class Log {

    /** Put your noisey stuff in here. */
    public const LEVEL_DEBUG = LOG_DEBUG;

    /** Important things, but not verbose. */
    public const LEVEL_INFO = LOG_INFO;

    /** Some needs to pay attention to this. */
    public const LEVEL_WARNING = LOG_WARNING;

    /** No really, this is bad. */
    public const LEVEL_ERROR = LOG_ERR;

    /** Shh. */
    public const LEVEL_SILENT = 0;


    /**
     * Get the friendly level name.
     *
     * @param int $level
     * @return string
     */
    public static function name(int $level): string
    {
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

        return '';
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
    public static function stringify($thing): string
    {
        // Objects with toStrings.
        if (is_object($thing) and is_callable([$thing, '__toString'])) {
            return $thing->__toString();
        }

        if ($thing instanceof Traversable) {
            $thing = iterator_to_array($thing);
        }

        // Easy stuff.
        if (is_array($thing)) {
            return json_encode($thing, JSON_PRETTY_PRINT);
        }

        // Some neat decimal places.
        if (is_float($thing)) {
            return sprintf('%.5f', $thing);
        }

        // Everything else.
        return (string) $thing;
    }
}
