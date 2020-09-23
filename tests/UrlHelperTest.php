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

    public function testResolve()
    {
        $expected = 'https://example.com/api/path/to/thing?param1=123&neat%5B0%5D=one&neat%5B1%5D=two';
        $actual = Url::build('https://example.com/api/', [
            '/path', '/to/thing',
            'param1' => 123,
            'neat' => ['one', 'two'],
        ]);

        $this->assertEquals($expected, $actual);
    }
}
