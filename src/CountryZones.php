<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Countries from timezones.
 *
 * @link https://github.com/unicode-org/cldr/blob/main/common/supplemental/windowsZones.xml
 * @package karmabunny\kb
 */
class CountryZones
{

    /**
     *
     * @return string[]
     */
    public static function getMap(): array
    {
        static $map;

        if (!isset($map)) {
            $map = require __DIR__ . '/config/tzcountry.php';
        }

        return $map;
    }


    /**
     *
     * @return string[][]
     */
    public static function getZoneMap()
    {
        $map = self::getMap();

        $zones = [];

        foreach ($map as $zone => $country) {
            $zones[$country][] = $zone;
        }

        return $zones;
    }


    /**
     *
     * @param string $country a country code, either ISO 3166-1 alpha-2 or alpha-3
     * @return string[] list of IANA timezones
     */
    public static function getZones(string $country): array
    {
        $map = self::getMap();

        if (strlen($country) === 3) {
            $country = CountryNames::getAlpha2From3($country);
        }

        $zones = [];

        foreach ($map as $zone => $name) {
            if ($name === $country) {
                $zones[] = $zone;
            }
        }

        return $zones;
    }


    /**
     *
     * @param string $zone
     * @param bool $alpha3
     * @return null|string
     */
    public static function lookup(string $zone, $alpha3 = false): ?string
    {
        $map = self::getMap();
        $zone = $map[$zone] ?? null;

        if (!$zone) return null;

        if ($alpha3) {
            $zone = CountryNames::getAlpha3From2($zone);
        }

        return $zone;
    }
}
