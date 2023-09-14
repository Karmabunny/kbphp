<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\CountryZones;
use PHPUnit\Framework\TestCase;

/**
 * Countries from timezones.
 */
final class CountryZonesTest extends TestCase
{


    public static function dataZones()
    {
        return [
            ['AU', [
                "Australia/Currie",
                "Antarctica/Macquarie",
                "Australia/Adelaide",
                "Australia/Brisbane",
                "Australia/Broken_Hill",
                "Australia/Darwin",
                "Australia/Eucla",
                "Australia/Hobart",
                "Australia/Lindeman",
                "Australia/Lord_Howe",
                "Australia/Melbourne",
                "Australia/Perth",
                "Australia/Sydney",
            ]],
            ['AUS', [
                "Australia/Currie",
                "Antarctica/Macquarie",
                "Australia/Adelaide",
                "Australia/Brisbane",
                "Australia/Broken_Hill",
                "Australia/Darwin",
                "Australia/Eucla",
                "Australia/Hobart",
                "Australia/Lindeman",
                "Australia/Lord_Howe",
                "Australia/Melbourne",
                "Australia/Perth",
                "Australia/Sydney",
            ]],
            ['NZ', [
                "Pacific/Auckland",
                "Pacific/Chatham",
            ]],
            ['NZL', [
                "Pacific/Auckland",
                "Pacific/Chatham",
            ]],
        ];
    }


    /**
     * @dataProvider dataZones
     */
    public function testZones($country, $expected)
    {
        $actual = CountryZones::getZones($country);
        sort($actual);
        sort($expected);
        $this->assertEquals($expected, $actual);
    }


    public static function dataLookup()
    {
        return [
            ['Australia/Sydney', 'AU', 'AUS'],
            ['Australia/Melbourne', 'AU', 'AUS'],
            ['Australia/Brisbane', 'AU', 'AUS'],
            ['America/Phoenix', 'US', 'USA'],
            ['America/Mazatlan', 'MX', 'MEX'],
            ['America/Denver', 'US', 'USA'],
            ['Pacific/Auckland', 'NZ', 'NZL'],
            ['Pacific/Chatham', 'NZ', 'NZL'],
        ];
    }


    /**
     * @dataProvider dataLookup
     */
    public function testLookup($zone, $alpha2, $alpha3)
    {
        $actual = CountryZones::lookup($zone, false);
        $this->assertEquals($alpha2, $actual);

        $actual = CountryZones::lookup($zone, true);
        $this->assertEquals($alpha3, $actual);
    }
}
