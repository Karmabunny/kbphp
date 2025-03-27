<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Time;
use PHPUnit\Framework\TestCase;

/**
 * Time utilities.
 */
final class TimeTest extends TestCase {

    public function testUtime()
    {
        $one = Time::utime(true);
        usleep(10000); // 10 msec
        $one = Time::utime(true) - $one;

        $this->assertGreaterThanOrEqual(10000, $one);
        $this->assertLessThanOrEqual(10500, $one);

        $two = Time::utime(false);
        usleep(10000); // 10 msec
        $two = Time::utime(false) - $two;

        $this->assertGreaterThanOrEqual(10000, $two);
        $this->assertLessThanOrEqual(10500, $two);

    }

    public function testTimeAgo()
    {
        $this->assertTrue(Time::timeAgo(0.35) == 'Just now');
        $this->assertTrue(Time::timeAgo(1.999) == 'Just now');
        $this->assertTrue(Time::timeAgo(0) == 'Just now');
        $this->assertTrue(Time::timeAgo(1) == 'Just now');
        $this->assertTrue(Time::timeAgo(2) == '2 seconds ago');
        $this->assertTrue(Time::timeAgo(59) == '59 seconds ago');
        $this->assertTrue(Time::timeAgo(60) == '1 minute ago');
        $this->assertTrue(Time::timeAgo(61) == '1 minute ago');
        $this->assertTrue(Time::timeAgo(60 + 59) == '1 minute ago');
        $this->assertTrue(Time::timeAgo(60 * 2) == '2 minutes ago');
        $this->assertTrue(Time::timeAgo(60 * 3) == '3 minutes ago');
        $this->assertTrue(Time::timeAgo(60 * 60 - 1) == '59 minutes ago');
        $this->assertTrue(Time::timeAgo(60 * 60) == '1 hour ago');
        $this->assertTrue(Time::timeAgo(60 * 60 + 1) == '1 hour ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 2) == '2 hours ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 3) == '3 hours ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 23) == '23 hours ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 23 + 1) == '23 hours ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 24 - 1) == '23 hours ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 24) == '1 day ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 24 + 1) == '1 day ago');
        $this->assertTrue(Time::timeAgo(60 * 60 * 24 * 2) == '2 days ago');
    }


    public function testIntervalModify()
    {
        $date = new DateTimeImmutable('2000-01-01');

        // duh.
        $interval = $date->diff($date->modify('+2 days'));
        $actual = $interval->format('%d');
        $this->assertEquals('2', $actual);

        // Oh.
        $modified = Time::modifyInterval($interval, '+2 days');
        $actual = $modified->format('%d');
        $this->assertEquals('4', $actual);

        $modified = Time::modifyInterval($modified, ['m' => 3]);
        $actual = $modified->format('%y %m %d');
        $this->assertEquals('0 3 4', $actual);

        $modified = Time::modifyInterval($modified, new DateInterval('P1Y'), new DateInterval('P2Y2M'));
        $actual = $modified->format('%y %m %d');
        $this->assertEquals('3 5 4', $actual);
    }


    public function testIntervalConfigs()
    {
        $spec = 'P1Y0M2DT40H100M0S';
        $interval = new DateInterval('P1Y2DT40H100M');

        // Test the strings bit too.
        $actual = Time::getIntervalString($interval);
        $this->assertEquals($spec, $actual);

        // Test creating a config.
        $actual = Time::getIntervalConfig($interval);
        $expected = [
            'y' => 1,
            'm' => 0,
            'd' => 2,
            'h' => 40,
            'i' => 100,
            's' => 0,
        ];
        $this->assertEquals($expected, $actual);

        // Test creating an interval back from that config.
        $actual = Time::createIntervalFromConfig($actual);
        $this->assertEquals($interval, $actual);

        // Test the string _again_.
        $actual = Time::getIntervalString($actual);
        $this->assertEquals($spec, $actual);
    }


    public function testPeriods()
    {
        $start = new DateTime('2020-10-10');
        $end = new Datetime('2020-10-20');

        $periods = Time::periods($start, $end, '+3 days');
        $dates = [];

        foreach ($periods as [$start, $end]) {
            $dates[] = $start->format('Y-m-d');
            $dates[] = $end->format('Y-m-d');
        }

        $this->assertEquals('2020-10-10', $dates[0]);
        $this->assertEquals('2020-10-13', $dates[1]);

        $this->assertEquals('2020-10-13', $dates[2]);
        $this->assertEquals('2020-10-16', $dates[3]);

        $this->assertEquals('2020-10-16', $dates[4]);
        $this->assertEquals('2020-10-19', $dates[5]);

        // The last entry is truncated.
        $this->assertEquals('2020-10-19', $dates[6]);
        $this->assertEquals('2020-10-20', $dates[7]);

        $this->assertFalse(isset($dates[8]));
    }


    public function testPeriodGaps()
    {
        $start = new DateTime('2020-10-01');
        $end = new Datetime('2020-10-20');

        $periods = Time::periods($start, $end, '+3 days', '+2 days');
        $dates = [];

        foreach ($periods as [$start, $end]) {
            $dates[] = $start->format('Y-m-d');
            $dates[] = $end->format('Y-m-d');
        }

        $this->assertEquals('2020-10-01', $dates[0]);
        $this->assertEquals('2020-10-04', $dates[1]);

        $this->assertEquals('2020-10-06', $dates[2]);
        $this->assertEquals('2020-10-09', $dates[3]);

        $this->assertEquals('2020-10-11', $dates[4]);
        $this->assertEquals('2020-10-14', $dates[5]);

        $this->assertEquals('2020-10-16', $dates[6]);
        $this->assertEquals('2020-10-19', $dates[7]);

        $this->assertFalse(isset($dates[8]));
    }


    public function testPeriodGapsTruncated()
    {
        $start = new DateTime('2020-10-01');
        $end = new Datetime('2020-10-20');

        $periods = Time::periods($start, $end, '+5 days', '+4 days');
        $dates = [];

        foreach ($periods as [$start, $end]) {
            $dates[] = $start->format('Y-m-d');
            $dates[] = $end->format('Y-m-d');
        }

        $this->assertEquals('2020-10-01', $dates[0]);
        $this->assertEquals('2020-10-06', $dates[1]);

        $this->assertEquals('2020-10-10', $dates[2]);
        $this->assertEquals('2020-10-15', $dates[3]);

        // The last entry is truncated.
        $this->assertEquals('2020-10-19', $dates[4]);
        $this->assertEquals('2020-10-20', $dates[5]);

        $this->assertFalse(isset($dates[6]));
    }


    public function testPeriodGapsNoSameTruncated()
    {
        $start = new DateTime('2020-10-01');
        $end = new Datetime('2020-10-19');

        $periods = Time::periods($start, $end, '+5 days', '+4 days');
        $dates = [];

        foreach ($periods as [$start, $end]) {
            $dates[] = $start->format('Y-m-d');
            $dates[] = $end->format('Y-m-d');
        }

        $this->assertEquals('2020-10-01', $dates[0]);
        $this->assertEquals('2020-10-06', $dates[1]);

        $this->assertEquals('2020-10-10', $dates[2]);
        $this->assertEquals('2020-10-15', $dates[3]);

        // This doesn't exist.
        $this->assertFalse(isset($dates[4]));
    }


    public function testMonths()
    {
        // Feb to Apr, 3 months.
        $months = Time::months(2021, 2, 4);
        $months = iterator_to_array($months);

        $this->assertCount(3, $months);
        $this->assertCount(28, $months[2]);
        $this->assertCount(31, $months[3]);
        $this->assertCount(30, $months[4]);

        $this->assertEquals($months[2][1]->format('Y-m-d'), '2021-02-01');
        $this->assertEquals($months[2][28]->format('Y-m-d'), '2021-02-28');

        $this->assertEquals($months[3][1]->format('Y-m-d'), '2021-03-01');
        $this->assertEquals($months[3][31]->format('Y-m-d'), '2021-03-31');

        $this->assertEquals($months[4][1]->format('Y-m-d'), '2021-04-01');
        $this->assertEquals($months[4][30]->format('Y-m-d'), '2021-04-30');
    }


    public function testMonthGrid()
    {
        // Jan to March.
        $months = Time::monthGrid(2021, 1, 3);
        $months = iterator_to_array($months);

        $this->assertCount(3, $months);
        $this->assertCount(5, $months[1]);
        $this->assertCount(4, $months[2]);
        $this->assertCount(5, $months[3]);

        // March, 2nd week, Saturday.
        $this->assertEquals('2021-03-13', $months[3][1][6]->format('Y-m-d'));
    }


    public function testNow()
    {
        $expected = '2020-' . date('m-d');
        $actual = Time::now(['year' => 2020])->format('Y-m-d');
        $this->assertEquals($expected, $actual);

        $expected = date('Y-01-01');
        $actual = Time::now(['month' => 1, 'day' => 1])->format('Y-m-d');
        $this->assertEquals($expected, $actual);
    }


    public function testWeekdays()
    {
        $actual = Time::weekdays();
        $expected = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
        $this->assertEquals($expected, $actual);

        $actual = Time::weekdays(3);
        $expected = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];
        $this->assertEquals($expected, $actual);
    }


    public function testTimeParse()
    {
        // Natural short time.
        $actual = Time::parseTimeString('1am');
        $expected = '1am';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 01:00:00', $date->format('Y-m-d H:i:s'));

        // Natural long time.
        $actual = Time::parseTimeString('03:31:45 pm');
        $expected = '03:31:45 pm';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 15:31:45', $date->format('Y-m-d H:i:s'));

        // Floats that looks like integers are ok.
        $actual = Time::parseTimeString(1300.0);
        $expected = 'T13:00:00';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 13:00:00', $date->format('Y-m-d H:i:s'));

        // Parsing T strings.
        $actual = Time::parseTimeString('T1345');
        $expected = 'T13:45:00';
        $this->assertEquals($expected, $actual);

        // A little ugly and weird.
        $actual = Time::parseTimeString('T134');
        $expected = 'T13:04:00';
        $this->assertEquals($expected, $actual);

        // A bit less ugly.
        $actual = Time::parseTimeString('T1340');
        $expected = 'T13:40:00';
        $this->assertEquals($expected, $actual);

        // Number looking strings are ok.
        // Single digits are padded left before they're padded right.
        $actual = Time::parseTimeString('1');
        $expected = 'T01:00:00';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 01:00:00', $date->format('Y-m-d H:i:s'));

        // 'T' prefixes are recommended.
        $actual = Time::parseTimeString('T1345');
        $expected = 'T13:45:00';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 13:45:00', $date->format('Y-m-d H:i:s'));

        // Subseconds are good too.
        $actual = Time::parseTimeString('T1.45.57.123123');
        $expected = 'T01:45:57.123123';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 01:45:57.123123', $date->format('Y-m-d H:i:s.u'));

        // Floats that looks like integers are ok.
        $actual = Time::parseTimeString(1300.0);
        $expected = 'T13:00:00';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 13:00:00', $date->format('Y-m-d H:i:s'));

        // Parsing microseconds also works.
        $actual = Time::parseTimeString(1300.123);
        $expected = 'T13:00:00.123';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 13:00:00.123', $date->format('Y-m-d H:i:s.v'));

        //
        // Little endian parsing.
        //

        // No change.
        $actual = Time::parseTimeString('T1345', false);
        $expected = 'T13:45:00';
        $this->assertEquals($expected, $actual);

        // Number looking strings a right aligned.
        $actual = Time::parseTimeString('1', false);
        $expected = 'T00:00:01';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 00:00:01', $date->format('Y-m-d H:i:s'));

        // Just yeah, keeps working like this.
        $actual = Time::parseTimeString('102', false);
        $expected = 'T00:01:02';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 00:01:02', $date->format('Y-m-d H:i:s'));

        // float also work.
        $actual = Time::parseTimeString('10234.456', false);
        $expected = 'T01:02:34.456';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 01:02:34.456', $date->format('Y-m-d H:i:s.v'));

        // T strings are unchanged.
        $actual = Time::parseTimeString('T1345', false);
        $expected = 'T13:45:00';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 13:45:00', $date->format('Y-m-d H:i:s'));
    }


    public function testMutable()
    {
        $date = new DateTime('now', new DateTimeZone('America/New_York'));

        $actual = Time::toDateTimeImmutable($date);
        $this->assertEquals($date->format('Y-m-d H:i:s'), $actual->format('Y-m-d H:i:s'));

        $actual = Time::toDateTime($date);
        $this->assertEquals($date->format('Y-m-d H:i:s'), $actual->format('Y-m-d H:i:s'));
    }


    public function dataTimestamp()
    {
        return [
            ['2024-11-12 04:18:24.227800'],
            ['2024-10-10 10:15:00.000000'],
            ['2024-01-01 00:00:00.000000'],
        ];
    }


    /** @dataProvider dataTimestamp */
    public function testTimestampFloats($date)
    {
        $expected = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $date);
        $timestamp = (float) $expected->format('U.u');

        $actual = Time::parseFloat($timestamp);
        $this->assertEquals($expected, $actual);

        $date = $expected;

        $expected = $timestamp;
        $actual = Time::toTimeFloat($date);
        $this->assertEquals($expected, $actual);

        $expected = (int) ($timestamp * 1000000);
        $actual = Time::toTimeMicroseconds($date);
        $this->assertEquals($expected, $actual);

        $expected = (int) ($timestamp * 1000);
        $actual = Time::toTimeMilliseconds($date);
        $this->assertEquals($expected, $actual);
    }


    public function dataTimezoneConvert()
    {
        return [
            ['Australia/Adelaide', '2024-11-01 10:30:00', '2024-11-01 00:00:00'],
            ['Australia/Melbourne', '2024-11-01 11:00:00', '2024-11-01 00:00:00'],
            ['Australia/Melbourne', '2024-11-01 12:30:00', '2024-11-01 01:30:00'],
            ['Australia/Brisbane', '2024-11-01 12:30:00', '2024-11-01 02:30:00'],
        ];
    }


    /** @dataProvider dataTimezoneConvert */
    public function testTimezoneConvert($timezone, $local, $utc)
    {
        $actual = Time::utcDateToLocal($timezone, $utc);
        $expected = $local;
        $this->assertEquals($expected, $actual);

        $actual = Time::utcDateToTime($timezone, $local);
        $expected = strtotime($utc);
        $this->assertEquals($expected, $actual);

        $actual = Time::utcDateToLocal($timezone, $utc);
        $expected = $local;
        $this->assertEquals($expected, $actual);

        $actual = Time::localDateToUtc($timezone, $local);
        $expected = $utc;
        $this->assertEquals($expected, $actual);


    }


    public function dataTimezoneOffset()
    {
        return [
            // Standard time offsets
            ['Australia/Adelaide', '+09:30', '2024-06-01'],
            ['America/New_York', '-05:00', '2024-01-15'],
            ['Europe/London', '+00:00', '2024-01-15'],
            ['Asia/Tokyo', '+09:00', '2024-01-15'],
            ['Pacific/Auckland', '+12:00', '2024-06-01'],

            // Daylight saving time offsets
            ['Australia/Adelaide', '+10:30', '2024-01-15'],
            ['America/New_York', '-04:00', '2024-06-01'],
            ['Europe/London', '+01:00', '2024-06-01'],
            ['Pacific/Auckland', '+13:00', '2024-01-15'],

            // Non-DST zones for comparison
            ['Asia/Bangkok', '+07:00', '2024-01-15'],
            ['UTC', '+00:00', '2024-01-15'],
        ];
    }


    /** @dataProvider dataTimezoneOffset */
    public function testTimezoneOffset($timezone, $expected, $testDate)
    {
        $actual = Time::getTimezoneOffset($timezone, strtotime($testDate));
        $this->assertEquals($expected, $actual);
    }
}
