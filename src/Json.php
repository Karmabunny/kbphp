<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use JsonException;

/**
 * Methods for handling JSON.
 *
 * @package karmabunny\kb
 */
abstract class Json
{

    public const RECURSIVE_DEPTH = 512;

    /**
     * Encode a json array as a string.
     *
     * @param mixed $json
     * @param bool $pretty
     * @return string
     * @throws JsonException Any parsing error
     */
    public static function encode($json, bool $pretty = false): string
    {
        $flags = 0;

        if ($pretty) {
            $flags |= JSON_UNESCAPED_SLASHES;
            $flags |= JSON_PRETTY_PRINT;
        }

        $out = json_encode($json, $flags);

        // PHP <= 7.2
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg(), $error);
        }

        return $out;
    }


    /**
     * Decode a JSON string, with objects converted into arrays
     *
     * @throws JsonException Any parsing error
     * @param string $str A JSON string. As per the spec, this should be UTF-8 encoded
     * @return mixed The decoded value
     */
    public static function decode(string $str)
    {
        $flags = 0;

        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            // phpcs:ignore
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $out = json_decode($str, true, self::RECURSIVE_DEPTH, $flags);

        // PHP <= 7.2
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg(), $error);
        }

        return $out;
    }
}
