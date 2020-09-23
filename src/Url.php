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
     * @param string $base
     * @param string|array $path
     * @return string
     */
    public static function build(string $base, $path): string
    {
        $url = '/';

        if (!is_array($path)) {
            $url .= $path;
        }
        else {
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
