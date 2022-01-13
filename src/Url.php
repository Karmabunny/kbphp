<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * URL helper methods.
 *
 * @package karmabunny\kb
 */
abstract class Url {

    /**
     *
     * @param array $query [key => value]
     * @return string
     */
    public static function encode(array $query): string
    {
        return http_build_query($query);
    }


    /**
     *
     * @param string $query
     * @return array [key => value]
     * @throws UrlDecodeException
     */
    public static function decode(string $query): array
    {
        $result = [];
        if (!mb_parse_str($query, $result)) {
            throw (new UrlDecodeException('Failed to parse query'))
                ->setQuery($query);
        }
        return $result;
    }


    /**
     * Cleanly joins base urls + paths.
     *
     * A path can be just a string or a bunch of fragments.
     *
     * [
     *    'path',
     *    'to',
     *    'thing',
     *    'param' => 123,
     *    'neat' => ['one', 'two'],
     * ]
     *
     * Should return:
     * '/path/to/thing?param=123&neat[0]=one&neat[1]=two'
     *
     * @param string|array $parts
     * @return string
     */
    public static function build(...$parts): string
    {
        if (empty($parts)) return '/';

        if (is_string($parts[0])) {
            $base = array_shift($parts);
        }
        else {
            $base = '/';
        }

        $url = '/';
        $path = [];

        foreach ($parts as $part) {
            if (is_array($part)) {
                $path = array_merge($path, $part);
            }
            else {
                array_push($path, $part);
            }
        }

        if (!empty($path)) {
            $parts = [];
            $query = [];

            foreach ($path as $key => $value) {
                if (is_numeric($key)) {
                    foreach (explode('/', $value) as $part) {
                        $parts[] = urlencode($part);
                    }
                }
                else {
                    $query[$key] = $value;
                }
            }

            $url .= implode('/', $parts);

            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }
        }

        return trim($base, '/') . preg_replace('/\/+/', '/', $url);
    }
}
