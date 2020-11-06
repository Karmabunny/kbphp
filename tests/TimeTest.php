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

        $this->assertEquals('2020-10-19', $dates[6]);
        $this->assertEquals('2020-10-20', $dates[7]);
    }
}
