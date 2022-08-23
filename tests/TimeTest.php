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
        $expected = 'T130000';

        $date = (new DateTime('2020-10-10'))->modify($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals('2020-10-10 13:00:00', $date->format('Y-m-d H:i:s'));


        $actual = Time::parseTimeString('T1345');
        $expected = 'T13:45:00';

        // Number looking strings are ok.
        // Single digits are padded left before they're padded right.
        $actual = Time::parseTimeString('1');
        $expected = 'T010000';

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
    }


    public function testMutable()
    {
        $date = new DateTime('now', new DateTimeZone('America/New_York'));

        $actual = Time::toDateTimeImmutable($date);
        $this->assertEquals($date->format('Y-m-d H:i:s'), $actual->format('Y-m-d H:i:s'));

        $actual = Time::toDateTime($date);
        $this->assertEquals($date->format('Y-m-d H:i:s'), $actual->format('Y-m-d H:i:s'));
    }
}
