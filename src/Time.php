<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;

/**
 * Various date and time utilities.
 *
 * @package karmabunny\kb;
 */
abstract class Time
{

    const MYSQL_DATE_FORMAT = 'Y-m-d H:i:s';


    /**
     * Timestamp as an integer in microseconds.
     *
     * This uses hrtime for 7.2+ with a microtime fallback.
     * You can force microtime by passing false.
     *
     * Note, if using hrtime the timestamp _is not_ a unix epoch.
     *
     * @param bool $hrtime Use high-resolution if available.
     * @return int microseconds
     */
    public static function utime($hrtime = true): int
    {
        if ($hrtime and function_exists('hrtime')) {
            return intdiv(hrtime(true), 1000);
        }
        else {
            return (int) (@microtime(true) * 1000000);
        }
    }


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


    /**
     *
     * Any date interface into a datetime.
     *
     * Legit, I think these are a builtin PHP 8 thing.
     *
     * Also, pretty sure that you can't actually implement the
     * DateTimeInterface, so this is pretty safe.
     *
     * @param DateTimeInterface $interface
     * @return DateTime
     */
    public static function toDateTime(DateTimeInterface $interface): DateTime
    {
        return $interface instanceof DateTimeImmutable
            ? DateTime::createFromImmutable($interface)
            : $interface;
    }


    /**
     * Any date interface into a immutable.
     *
     * Legit, I think these are a builtin PHP 8 thing.
     *
     * Also, pretty sure that you can't actually implement the
     * DateTimeInterface, so this is pretty safe.
     *
     * @param DateTimeInterface $interface
     * @return DateTimeImmutable
     */
    public static function toDateTimeImmutable(DateTimeInterface $interface): DateTimeImmutable
    {
        return $interface instanceof DateTime
            ? DateTimeImmutable::createFromMutable($interface)
            : $interface;
    }


    /**
     * Get a series of date periods between these two dates.
     *
     * For a 3 day period between 1st and 10th:
     *  0: 1, 3
     *  1: 3, 6
     *  2: 6, 9,
     *  3: 9, 10
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param string $period A date modifier, like '+2 days'
     * @return Generator<DateTimeInterface[]>
     */
    public static function periods(DateTimeInterface $start, DateTimeInterface $end, string $period)
    {
        $start = self::toDateTimeImmutable($start);

        while ($start < $end) {
            $cursor = $start->modify($period);

            // Don't overshoot - limit the end date.
            if ($cursor > $end) {
                $cursor = $end;
            }

            yield [
                $start,
                $cursor,
            ];

            $start = $cursor;
        }
    }
}
