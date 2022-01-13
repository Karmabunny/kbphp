<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

use karmabunny\kb\Json;
use PHPUnit\Framework\TestCase;

/**
 * Test the JSON helper.
 */
final class JsonTest extends TestCase {

    public function testJsonError()
    {
        $previous = new Error('more error');
        $error = new Error('this is an error', 0, $previous);

        $actual = Json::error($error);

        // Main body.
        $this->assertEquals('this is an error', $actual['message']);
        $this->assertEquals(0, $actual['code']);
        $this->assertEquals(__FILE__, $actual['file']);
        $this->assertEquals($error->getLine(), $actual['line']);
        $this->assertEquals('Error', $actual['name']);

        // Previous bits.
        $this->assertEquals('more error', $actual['previous']['message']);
        $this->assertEquals(0, $actual['previous']['code']);
        $this->assertEquals(__FILE__, $actual['previous']['file']);
        $this->assertEquals($previous->getLine(), $actual['previous']['line']);
        $this->assertEquals('Error', $actual['previous']['name']);

        // Limited stack asserts.
        $this->assertGreaterThan(2, count($actual['stack']));

        $this->assertEquals('testJsonError', $actual['stack'][0]['function']);
        $this->assertEquals('JsonTest', $actual['stack'][0]['class']);
        $this->assertEquals('->', $actual['stack'][0]['type']);

        $this->assertEquals('runTest', $actual['stack'][1]['function']);
        $this->assertEquals(TestCase::class, $actual['stack'][1]['class']);
        $this->assertEquals('->', $actual['stack'][1]['type']);
    }
}
