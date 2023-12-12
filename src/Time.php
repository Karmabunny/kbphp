<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use InvalidArgumentException;

/**
 * Various date and time utilities.
 *
 * @package karmabunny\kb;
 */
class Time
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
     * Parse date strings and numbers into objects.
     *
     * Notable features:
     * - pass-through date objects
     * - parse integer as unix timestamps
     * - support for microsecond timestamps (as float)
     * - classic PHP date parsing
     * - timezones
     *
     * @param string|int|float|DateTimeInterface $date
     * @param string|DateTimeZone|null $zone
     * @return DateTimeInterface
     * @throws InvalidArgumentException
     */
    public static function parse($value, $zone = null): DateTimeInterface
    {
        // Parse integer/floats as timestamps with microseconds.
        if (is_numeric($value)) {
            $seconds = floor($value);

            $date = new DateTimeImmutable('@' . $seconds);

            // No microseconds.
            if ($seconds == $value) {
                return $date;
            }

            $microseconds = floor(($value - $seconds) * 1000000);
            $date = $date->modify("+{$microseconds} microseconds");

            if ($date === false) {
                throw new InvalidArgumentException('Invalid date value: '. $value);
            }
        }

        // Classic timey-wimey parsing.
        else if (is_string($value)) {
            $date = new DateTimeImmutable($value);
        }

        // Pass-through these ones, it's safe.
        else if ($value instanceof DateTimeImmutable) {
            $date = $value;
        }

        // Clone everything else to prevent mutation bugs.
        else if ($value instanceof DateTimeInterface) {
            $date = clone $value;
        }

        if (!isset($date)) {
            throw new InvalidArgumentException('Invalid date value: '. gettype($value));
        }

        if ($zone) {
            // Also parse timezones while we're here.
            if (is_string($zone)) {
                $zone = new DateTimeZone($zone);
            }

            /** @var DateTime|DateTimeInterface $date */

            $date = $date->setTimezone($zone);
        }

        return $date;
    }


    /**
     * Validate + normalize a time string component.
     *
     * A time-ish value is one of:
     *
     *  - an integer (24-hour)
     *  - a float (24-hour with milliseconds)
     *  - starts with T (24-hour)
     *  - ends with am/pm (12-hour)
     *
     * When parsing numbers (without a T prefix) the alignment can be either
     * big endian or little endian.
     *
     * - Big endian (left align) means hours first: `1 => T01:00:00`
     * - Little endian (right align) means seconds first: `1 => T00:00:01`
     *
     * Values with a 'T' prefix are _always_ big endian. Such as:
     *
     * `T101 => 'T10:10:00'`
     *
     * @param string|int $time
     * @param bool $big_endian left or right aligned number parsing
     * @return string|null `T HH:MM:II.SSS`
     */
    public static function parseTimeString($time, $big_endian = true)
    {
        // 24 hour time.
        if (is_numeric($time)) {
            $args = [];

            // hours:   10       -> T10
            // minutes: 1020     -> T10:20
            // seconds: 102030   -> T10:20:30
            // milli:   102030.4 -> T10:20:30.400
            if ($big_endian) {
                if ($time < 24) {
                    $format = 'T%02d:00:00';
                    $args[] = (int) $time;
                }
                else if ($time < 2400) {
                    $format = 'T%02d:%02d:00';
                    $args[] = floor($time / 100);
                    $args[] = (int) $time % 100;
                }
                else {
                    $format = 'T%02d:%02d:%02d';
                    $args[] = floor($time / 10000);
                    $args[] = floor(((int) $time % 10000) / 100);
                    $args[] = (int) $time % 100;
                }
            }
            // hours:   100000   -> T10
            // minutes: 102000   -> T10:20
            // seconds: 102030   -> T10:20:30
            // milli:   102030.4 -> T10:20:30.400
            else {
                $format = 'T%02d:%02d:%02d';
                $args[] = floor($time / 10000);
                $args[] = floor(((int) $time % 10000) / 100);
                $args[] = (int) $time % 100;
            }

            $subsecond = $time - floor($time);
            $subsecond = floor($subsecond * 1000);

            if ($subsecond > 0) {
                $format .= '.%03d';
                $args[] = $subsecond;
            }

            return vsprintf($format, $args);
        }

        if (is_string($time)) {
            // 12-hour time, like: 12 pm, 12:00pm, 1:30am.
            if (preg_match('/^[0-9:\.]+\s*([ap]\.?m\.?)$/i', $time)) {
                return $time;
            }

            $matches = [];

            // 24-hour time.
            if (preg_match('/^T(\d{1,2})[:\.]?(\d{0,2})[:\.]?(\d{0,2})\.?(\d*)$/', $time, $matches)) {

                list($_, $hour, $minute, $second, $subsecond) = $matches;
                // We gotta tear up and reconstruct this one. I want it so
                // a 'T12' will be '12:00:00' with an optional subsecond.
                $format = 'T%02d:%02d:%02d';
                $args[] = min(24, $hour ?: 0);
                $args[] = min(60, $minute ?: 0);
                $args[] = min(60, $second ?: 0);

                if ($subsecond) {
                    $format .= '.%03d';
                    $args[] = $subsecond;
                }

                return vsprintf($format, $args);
            }
        }

        // No good.
        return null;
    }


    /**
     * Convert any date interface into a datetime.
     *
     * This exists in PHP8+ as `DateTime::createFromInterface()`.
     *
     * @param DateTimeInterface $interface
     * @return DateTime
     */
    public static function toDateTime(DateTimeInterface $interface): DateTime
    {
        if ($interface instanceof DateTime) {
            return $interface;
        }

        $date = new DateTime();
        $date->setTimestamp($interface->getTimestamp());
        $date->setTimezone($interface->getTimezone());
        return $date;
    }


    /**
     * Convert any date interface into a immutable.
     *
     * This exists in PHP8+ as `DateTimeImmutable::createFromInterface()`.
     *
     * @param DateTimeInterface $interface
     * @return DateTimeImmutable
     */
    public static function toDateTimeImmutable(DateTimeInterface $interface): DateTimeImmutable
    {
        if ($interface instanceof DateTimeImmutable) {
            return $interface;
        }

        // One can't actually implement DateTimeInterface without inheriting
        // one of the concrete DateTime or DateTimeImmutable classes, so this
        // is pretty safe.
        return DateTimeImmutable::createFromMutable($interface);
    }


    /**
     * Modify an interval.
     *
     * As such, a contrived example:
     * ```
     * $date = new DateTimeImmutable('2000-01-01');
     *
     * $interval = $date->diff($date->modify('+2 days'));
     * $interval->format('%a days');
     * // => 2 days
     *
     * $modified = modifyInterval($interval, '+2 days');
     * $modified->format('%a days');
     * // => 4 days
     * ```
     *
     * @param DateInterval|array|string $intervals
     * @return DateInterval
     */
    public static function modifyInterval(...$intervals)
    {
        static $UNITS = ['y', 'm', 'd', 'h', 'i', 's'];

        $config = array_fill_keys($UNITS, 0);

        foreach ($intervals as $interval) {
            if (is_array($interval)) {
                $interval = array_change_key_case($interval, CASE_LOWER);

                foreach ($UNITS as $unit) {
                    $config[$unit] += $interval[$unit] ?? 0;
                }
            }
            else {
                if (is_string($interval)) {
                    $interval = DateInterval::createFromDateString($interval);
                }

                foreach ($UNITS as $unit) {
                    $config[$unit] += $interval->$unit;
                }
            }
        }

        $interval = self::createIntervalFromConfig($config);
        return $interval;
    }


    /**
     * Create an interval config.
     *
     * This can be used to serialise an interval.
     *
     * @param DateInterval $interval
     * @return array
     */
    public static function getIntervalConfig(DateInterval $interval): array
    {
        static $UNITS = ['y', 'm', 'd', 'h', 'i', 's'];

        $config = [];

        foreach ($UNITS as $unit) {
            $config[$unit] = $interval->$unit;
        }

        return $config;
    }


    /**
     * Create an interval from a config.
     *
     * A config is a series of component keys:
     *
     * - y: years
     * - m: months
     * - d: days
     * - h: hours
     * - i: minutes
     * - s: seconds
     *
     * @param array $config
     * @return DateInterval
     */
    public static function createIntervalFromConfig(array $config): DateInterval
    {
        $config = array_change_key_case($config, CASE_LOWER);

        $interval = 'P';
        $interval .= ($config['y'] ?? 0) . 'Y';
        $interval .= ($config['m'] ?? 0) . 'M';
        $interval .= ($config['d'] ?? 0) . 'D';
        $interval .= 'T';
        $interval .= ($config['h'] ?? 0) . 'H';
        $interval .= ($config['i'] ?? 0) . 'M';
        $interval .= ($config['s'] ?? 0) . 'S';

        $interval = new DateInterval($interval);
        return $interval;
    }


    /**
     * Get the interval specification string.
     *
     * This can be used to serialise intervals.
     *
     * @param DateInterval $interval
     * @return string
     */
    public static function getIntervalString(DateInterval $interval): string
    {
        return $interval->format('P%yY%mM%dDT%hH%iM%sS');
    }


    /**
     * Get a series of date periods between these two dates.
     *
     * For a 3 day period between 1st and 20th:
     *
     *  - 0: `[01-01-2000, 04-01-2000]`
     *  - 1: `[04-01-2000, 07-01-2000]`
     *  - 2: `[07-01-2000, 10-01-2000]`
     *  - 3: `[10-01-2000, 13-01-2000]`
     *  - 4: `[13-01-2000, 16-01-2000]`
     *  - 5: `[16-01-2000, 19-01-2000]`
     *  - 6: `[19-01-2000, 20-01-2000]`
     *
     * The last item will be truncated to the end of the period.
     *
     * Provide the 'gap' argument insert a gap between the periods.
     * For example, the same as above but with a 2 day gap:
     *
     * - 0: `[01-01-2000, 03-01-2000]`
     * - 1: `[05-02-2000, 08-01-2000]`
     * - 2: `[10-02-2000, 13-01-2000]`
     * - 3: `[15-02-2000, 18-01-2000]`
     *
     * The last item will be truncated if necessary but there's no guarantee
     * that the last days will
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param string $period A date modifier, like '+2 days'
     * @param string|null $gap A date modifier, a gap between each period
     * @return Generator<int,DateTimeInterface[]> [start, end]
     */
    public static function periods(DateTimeInterface $start, DateTimeInterface $end, string $period, string $gap = null)
    {
        $start = self::toDateTimeImmutable($start);
        $end = self::toDateTimeImmutable($end);

        $periodStart = $start;
        $periodEnd = $end;

        while ($periodStart < $end) {
            $periodEnd = $periodStart->modify($period);

            // Don't overshoot - limit the end date.
            if ($periodEnd > $end) {
                $periodEnd = $end;
            }

            yield [
                $periodStart,
                $periodEnd,
            ];

            $periodStart = $gap ? $periodEnd->modify($gap) : $periodEnd;
        }
    }


    /**
     * Get a series of dates between these two dates.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @return iterable<DateTimeInterface>
     */
    public static function between(DateTimeInterface $start, DateTimeInterface $end)
    {
        $periods = self::periods($start, $end, '+1 day');
        foreach ($periods as $days) yield $days[0];
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
     * @return iterable<DateTimeInterface[]>
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
     * @return iterable<DateTimeInterface[][]>
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
            unset($day);
        }

        return $days;
    }
}
