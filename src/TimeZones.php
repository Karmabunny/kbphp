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

    protected static ?array $map = null;


    /**
     * Get a map of Windows timezones to IANA timezones.
     *
     * @return array<string,string>
     */
    public static function getMap(): array
    {
        if (self::$map === null) {
            self::$map = require __DIR__ . '/config/tzwin.php';
        }

        $map = self::$map;
        unset($map['__rev__']);
        return $map;
    }


    /**
     * Get a map of IANA timezones to Windows timezones.
     *
     * @return array<string,string>
     */
    public static function getIanaMap(): array
    {
        self::getMap();
        return self::$map['__rev__'] ?? [];
    }


    /**
     * Translate between Windows and IANA timezones.
     *
     * @param string $name a windows-style timezone
     * @return null|string IANA timezone
     */
    public static function fromWindows(string $name): ?string
    {
        $map = self::getMap();
        return $map[$name] ?? null;
    }


    /**
     * Translate from IANA to Windows timezones.
     *
     * @param string $name IANA timezone
     * @return null|string a windows-style timezone
     */
    public static function fromIana(string $name): ?string
    {
        $map = self::getIanaMap();
        return $map[$name] ?? null;
    }


    /**
     * Normalize both IANA and Windows timezones into IANA.
     *
     * @param string $name IANA or windows timezone
     * @return null|string IANA timezone
     */
    public static function normalize(string $name): ?string
    {
        try {
            return self::parse($name)->getName();
        }
        catch (Exception $error) {
            return null;
        }
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
        $tz = self::fromWindows($name);

        if ($tz) {
            return new DateTimeZone($tz);
        }

        $tz = new DateTimeZone($name);
        return $tz;
    }


    /**
     * Convert a timezone object into a Windows timezone.
     *
     * @param DateTimeZone $zone
     * @return null|string
     */
    public static function toWindows(DateTimeZone $zone): ?string
    {
        $name = $zone->getName();
        $tz = self::fromIana($name);
        return $tz;
    }
}
