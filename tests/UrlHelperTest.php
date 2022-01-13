<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Url;
use PHPUnit\Framework\TestCase;

/**
 * Test the URL helper utilities.
 */
final class UrlHelperTest extends TestCase {

    public function testWithBaseUrl()
    {
        // Typical usage: the base url isn't cleaned, the path built and _is_ cleaned.
        $expected = 'https://example.com/api/path/to%3F/thing?param1=123&neat%5B0%5D=one&neat%5B1%5D=two';
        $actual = Url::build('https://example.com/api/', [
            '/path', '/to?/thing',
            'param1' => 123,
            'neat' => ['one', 'two'],
        ]);

        $this->assertEquals($expected, $actual);
    }


    public function testDumbPaths()
    {
        // The 'dumb' version will trash the queries.
        // But the base url and path should be fine.
        $expected = 'https://okay.com/it/is/%3Fall%3Dgood%26hah%5B0%5D%3D123';
        $actual = Url::build('https://okay.com', '///it/is', '?all=good&hah[0]=123');

        $this->assertEquals($expected, $actual);

        // The correct way would be to decode it first.
        // Something like this.
        $expected = 'https://okay.com/it/is?all=good&hah%5B0%5D=123';
        [$path, $query] = explode('?', '///it/is?all=good&hah[0]=123');
        $actual = Url::build('https://okay.com', $path, Url::decode($query));

        $this->assertEquals($expected, $actual);
    }


    public function testBuildPath()
    {
        // A single path argument builds a path as expected and cleans everything.
        $expected = '/it/is?all=good&hah%5B0%5D=123';
        $actual = Url::build([
            '//it//', 'is',
            'all' => 'good',
            'hah' => [123],
        ]);

        $this->assertEquals($expected, $actual);
    }


    public function testNullParams()
    {
        $expected = '/test/ok?param2=0&param3=1';
        $actual = Url::build([
            'test', 'ok',
            'param1' => null,
            'param2' => false,
            'param3' => true,
        ]);

        $this->assertEquals($expected, $actual);
    }
}
