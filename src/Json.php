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
abstract class Json {

    /**
     * Decode a JSON string, with objects converted into arrays
     *
     * @throws JsonException Any parsing error
     * @param string $str A JSON string. As per the spec, this should be UTF-8 encoded
     * @return mixed The decoded value
     */
    public static function decode(string $str)
    {
        $out = json_decode($str, true);
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg(), $error);
        }

        return $out;
    }
}
