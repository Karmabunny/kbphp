<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use DateTimeZone;
use Exception;

/**
 * Utilities for Windows timezones - which is straight up bonkers.
 *
 * @link https://github.com/unicode-org/cldr/blob/main/common/supplemental/windowsZones.xml
 * @package karmabunny\kb
 */
class TimeZones
{

    /**
     *
     * @return array
     */
    public static function getMap(): array
    {
        static $map;

        if (!isset($map)) {
            $map = require __DIR__ . '/config/tzwin.php';
        }

        return $map;
    }


    /**
     * Translate between Windows and IANA timezones.
     *
     * @param string $name a windows-style timezone
     * @return null|string IANA timezone
     */
    public static function lookup(string $name): ?string
    {
        $map = self::getMap();
        return $map[$name] ?? null;
    }


    /**
     * Convert this name into a timezone.
     *
     * This accepts both IANA and Windows timezones.
     * Offsets using 'GMT-XXXX' or 'GMT+XXXX' are naturally accepted by PHP.
     *
     * @param string $name
     * @return DateTimeZone
     * @throws Exception
     */
    public static function parse(string $name): DateTimeZone
    {
        $tz = self::lookup($name);

        if ($tz) {
            return new DateTimeZone($tz);
        }

        $tz = new DateTimeZone($name);
        return $tz;
    }
}
