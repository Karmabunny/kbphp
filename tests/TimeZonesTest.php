<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\TimeZones;
use PHPUnit\Framework\TestCase;

/**
 * Timezone conversion.
 */
final class TimeZonesTest extends TestCase
{


    public static function dataWindows()
    {
        return [
            ['AUS Eastern Standard Time', 'Australia/Sydney'],
            ['E. Australia Standard Time', 'Australia/Brisbane'],
            ['US Mountain Standard Time', 'America/Phoenix'],
            ['Mountain Standard Time (Mexico)', 'America/Mazatlan'],
            ['Mountain Standard Time', 'America/Denver'],
            ['UTC', 'UTC'],
        ];
    }


    /**
     * @dataProvider dataWindows
     */
    public function testWindows($zone, $expected)
    {
        $actual = TimeZones::fromWindows($zone);
        $this->assertEquals($expected, $actual);
    }


    public static function dataIana()
    {
        return [
            ['Australia/Sydney', 'AUS Eastern Standard Time'],
            ['Australia/Brisbane', 'E. Australia Standard Time'],
            ['America/Phoenix', 'US Mountain Standard Time'],
            ['America/Mazatlan', 'Mountain Standard Time (Mexico)'],
            ['America/Denver', 'Mountain Standard Time'],
            ['UTC', 'UTC'],
            ['Australia/Melbourne', 'AUS Eastern Standard Time'],
            ['America/Boise', 'Mountain Standard Time'],
        ];
    }


    /**
     * @dataProvider dataIana
     */
    public function testIana($zone, $expected)
    {
        $actual = TimeZones::fromIana($zone);
        $this->assertEquals($expected, $actual);
    }


    public function testNotFound()
    {
        $actual = TimeZones::fromWindows('not real');
        $this->assertNull($actual);

        $actual = TimeZones::fromIana('not real');
        $this->assertNull($actual);
    }


    public static function dataNormalize()
    {
        return [
            ['AUS Eastern Standard Time', 'Australia/Sydney'],
            ['E. Australia Standard Time', 'Australia/Brisbane'],
            ['US Mountain Standard Time', 'America/Phoenix'],
            ['Mountain Standard Time (Mexico)', 'America/Mazatlan'],
            ['Mountain Standard Time', 'America/Denver'],
            ['Australia/Sydney', 'Australia/Sydney'],
            ['Australia/Brisbane', 'Australia/Brisbane'],
            ['America/Phoenix', 'America/Phoenix'],
            ['America/Denver', 'America/Denver'],
            ['UTC', 'UTC'],
            ['not/sydney', null],
            ['not real', null],
        ];
    }


    /**
     * @dataProvider dataNormalize
     */
    public function testNormalize($zone, $expected)
    {
        $actual = TimeZones::normalize($zone);
        $this->assertEquals($expected, $actual);
    }

}
