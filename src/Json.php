<?php
declare(strict_types=1);
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use JsonException;
use JsonSerializable;
use Throwable;

/**
 * Methods for handling JSON.
 *
 * @package karmabunny\kb
 */
class Json
{

    const RECURSIVE_DEPTH = 512;


    /**
     * Encode a json array as a string.
     *
     * @param mixed $json
     * @param bool|int $flags Applies pretty flags if `true`, default: `JSON_THROW_ON_ERROR`
     * @return string
     * @throws JsonException Any parsing error
     */
    public static function encode(mixed $json, bool|int $flags = -1): string
    {
        if ($flags === true) {
            $flags = 0;
            $flags |= JSON_UNESCAPED_SLASHES;
            $flags |= JSON_PRETTY_PRINT;
        }
        else if ($flags === -1) {
            $flags = 0;
            $flags |= JSON_THROW_ON_ERROR;
        }

        $out = json_encode($json, $flags);
        return $out;
    }


    /**
     * Decode a JSON string, with objects converted into arrays
     *
     * @throws JsonException Any parsing error
     * @param string $str A JSON string. As per the spec, this should be UTF-8 encoded
     * @param int $flags Default: `JSON_INVALID_UTF8_SUBSTITUTE` and `JSON_THROW_ON_ERROR`
     * @return mixed The decoded value
     */
    public static function decode(string $str, int $flags = -1): mixed
    {
        if ($flags === -1) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
            $flags |= JSON_THROW_ON_ERROR;
        }

        $out = json_decode($str, true, self::RECURSIVE_DEPTH, $flags);
        return $out;
    }


    /**
     * Convert an error/exception into a JSON body.
     *
     * @param Throwable $error
     * @param bool $serialized use JsonSerializable if available.
     * @return array
     */
    public static function error(Throwable $error, bool $serialized = true): array
    {
        // Use the serializable interface.
        // This may not be desirable if you're using _this helper_ to implement
        // the error serializable.
        if ($serialized and $error instanceof JsonSerializable) {
            return $error->jsonSerialize();
        }

        return [
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'name' => get_class($error),
            'previous' => (
                ($previous = $error->getPrevious())
                ? self::error($previous)
                : null
            ),
            'stack' => $error->getTrace(),
        ];
    }
}
