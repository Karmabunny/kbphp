<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Various date and time utilities.
 *
 * @package karmabunny\kb;
 */
abstract class Time
{

    const MYSQL_DATE_FORMAT = 'Y-m-d H:i:s';


    /**
     * Returns a time in 'x minutes ago' format.
     *
     * Very small times (0, 1 seconds) are considered 'Just now'.
     * Times are represented in seconds, minutes, hours or days.
     *
     * @param int $timediff Amount of time that has passed, in seconds.
     * @return string
     **/
    public static function timeAgo(int $timediff)
    {
        $timediff = (int) $timediff;

        if ($timediff < 2) return 'Just now';

        if ($timediff >= 86400) {
            $unit = ' day';
            $time = floor($timediff / 86400);

        } else if ($timediff >= 3600) {
            $unit = ' hour';
            $time = floor($timediff / 3600);

        } else if ($timediff >= 60) {
            $unit = ' minute';
            $time = floor($timediff / 60);

        } else {
            $unit = ' second';
            $time = $timediff;

        }

        return $time . $unit . ($time == 1 ? ' ago' : 's ago');
    }
}
