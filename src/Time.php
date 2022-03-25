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

    const COMPONENT_MAP = [
        'year' => 'Y',
        'month' => 'm',
        'day' => 'd',
        'hour' => 'H',
        'minute' => 'i',
        'second' => 's',
    ];

    const EMPTY_WEEK = [
        1 => null,
        2 => null,
        3 => null,
        4 => null,
        5 => null,
        6 => null,
        7 => null,
    ];


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
            // phpcs:ignore
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
     * Validate + normalize a time string component.
     *
     * A time-ish string is one of:
     *
     *  - an integer (24-hour)
     *  - starts with T (24-hour)
     *  - ends with am/pm (12-hour)
     *
     * This should always return a whole time component. So all units will be
     * updated (hour, minute, second, sub-second) if used in `$date->modify()`.
     *
     * That is;
     * - `14` will be 'T14:00:00'
     * - `T12:34:56.789` will be 'T12:34:56'
     * - `12am` naturally strips minutes/seconds/etc
     *
     * @param string|int $time
     * @return string|null
     */
    public static function parseTimeString($time)
    {
        // 24 hour time.
        // hours: 10 -> T100000 (10 am)
        // minutes: 1020 -> T102000 (10:20 am)
        // seconds: 102030 -> T102030 (10:20:30 am)
        if (is_numeric($time) and (int) $time == (float) $time) {

            // One would naturally expect '1' to make '1am' not '10am'.
            if ($time < 10) {
                $time = '0' . $time;
            }

            return 'T' . str_pad($time, 6, '0', STR_PAD_RIGHT);
        }

        if (is_string($time)) {
            // 12-hour time, like: 12 pm, 12:00pm, 1:30am.
            if (preg_match('/^[0-9:\.]+\s*([ap]\.?m\.?)$/i', $time)) {
                return $time;
            }

            $matches = [];

            // 24-hour time.
            if (preg_match('/^T(\d{1,2})[:\.]?(\d{0,2})[:\.]?(\d{0,2})\.?(\d*)$/', $time, $matches)) {

                [$_, $hour, $minute, $second, $subsecond] = $matches;
                // We gotta tear up and reconstruct this one. I want it so
                // a 'T12' will be '12:00:00' with an optional subsecond.
                $time = 'T';
                $time .= str_pad(min(24, $hour ?: '0'), 2, '0', STR_PAD_LEFT);
                $time .= ':' . str_pad(min(60, $minute ?: '0'), 2, '0', STR_PAD_LEFT);
                $time .= ':' . str_pad(min(60, $second ?: '0'), 2, '0', STR_PAD_LEFT);

                if ($subsecond) {
                    $time .= '.' . str_pad($subsecond ?: '0', 3, '0', STR_PAD_RIGHT);
                }

                return $time;
            }
        }

        // No good.
        return null;
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
        // @phpstan-ignore-next-line : Can't extend DateTimeInterface. It's one or the other.
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
        // @phpstan-ignore-next-line : Can't extend DateTimeInterface. It's one or the other.
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
     * @return Generator<DateTimeInterface[]> [start, end]
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


    /**
     * Get a series of dates between these two dates.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @return Generator<DateTimeInterface>
     */
    public static function between(DateTimeInterface $start, DateTimeInterface $end)
    {
        $periods = self::periods($start, $end, '+1 day');
        foreach ($periods as [$day]) yield $day;
    }


    /**
     * Get groups of months.
     *
     * All inputs and outputs are 1-indexed.
     *
     * For example:
     *   Time::months(2021, 1, 12) => January to December
     *   $months[1] => January
     *   $months[12] => December
     *   $months[2][1] => Feb 1st
     *   $months[2][28] => Feb 28th
     *
     * @param int $year
     * @param int $from 1-indexed, inclusive
     * @param int $to 1-indexed, inclusive
     * @return Generator<DateTimeInterface[]>
     */
    public static function months(int $year, int $from, int $to)
    {

        while ($from <= $to) {
            $cursor = new DateTimeImmutable("{$year}-{$from}-01");
            $count = (int) $cursor->format('t');

            $days = [];

            for ($day = 1; $day <= $count; $day++) {
                $days[$day] = new DateTimeImmutable("{$year}-{$from}-{$day}");
            }

            yield $from => $days;
            $from += 1;
        }
    }


    /**
     * Get week aligned months.
     *
     * For example: April 2021
     * ```
     *   Mo Tu We Th Fr Sa Su
     *    -  -  -  1  2  3  4
     *    5  6  7  8  9 10 11
     *   12 13 14 15 16 17 18
     *   19 20 21 22 23 24 25
     *   26 27 28 29 30  -  -
     * ```
     *
     * The output is a 3-dimension array.
     *
     *   [month][week][day] => DateTimeInterface
     *
     * @param int $year
     * @param int $from 1-indexed, inclusive
     * @param int $to 1-indexed, inclusive
     * @return Generator<DateTimeInterface[][]>
     */
    public static function monthGrid(int $year, int $from, int $to)
    {
        $months = self::months($year, $from, $to);

        foreach ($months as $month => $days) {
            $week = self::EMPTY_WEEK;
            $weeks = [];

            foreach ($days as $day) {
                $dow = (int) $day->format('N');
                $week[$dow] = $day;

                if ($dow === 7) {
                    $weeks[] = $week;
                    $week = self::EMPTY_WEEK;
                }
            }

            if ($week[1] and !$week[7]) {
                $weeks[] = $week;
            }

            yield $month => $weeks;
        }
    }


    /**
     * Get the current date and selectively replace components.
     *
     * Example:
     * ```
     *   // 'today' is 2021-02-03 14:30:10
     *   Time::now(['year' => 2000]);
     *   // => '2000-02-03 14:30:10'
     *
     *   // Don't forget that `\date()` does this too:
     *   date('2000-m-d');
     *   // => 2000-02-03
     *
     *   // But `new DateTime()` does not:
     *   new DateTime('2000-m-d');
     *   // => throws error
     * ```
     *
     * Config keys:
     * - year
     * - month
     * - day
     * - hour
     * - minute
     * - second
     *
     * @param array|string $config
     * @return DateTimeImmutable
     */
    public static function now($config = []): DateTimeInterface
    {
        $now = new DateTimeImmutable();

        foreach (self::COMPONENT_MAP as $key => $format) {
            if (!isset($config[$key])) {
                $config[$key] = $now->format($format);
            }
        }

        $date = "{$config['year']}-{$config['month']}-{$config['day']}";
        $time = "{$config['hour']}:{$config['minute']}:{$config['second']}";

        return new DateTimeImmutable("{$date}T{$time}");
    }


    /**
     * List of week day names (starting from Monday).
     *
     * @param int $length substring length
     * @return string[]
     */
    public static function weekdays(int $length = 0): array
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        if ($length) {
            foreach ($days as &$day) {
                $day = substr($day, 0, $length);
            }
        }

        return $days;
    }
}
