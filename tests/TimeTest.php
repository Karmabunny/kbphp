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
}
