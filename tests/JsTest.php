<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

use karmabunny\kb\Js;
use karmabunny\kb\JsDate;
use karmabunny\kb\JsFunction;
use karmabunny\kb\JsRaw;
use PHPUnit\Framework\TestCase;

/**
 * Test the JS helper and expressions.
 */
final class JsTest extends TestCase
{


    public function dataJsExpression(): array
    {
        return [
            'simple' => [
                [ 'one' => 100, 'two' => new JsRaw('95 + 5') ],
                '{"one":100,"two":95 + 5}'
            ],
            'function arrow' => [
                [ 'one' => 100, 'two' => new JsFunction(['test'], 'Math.random() * test', true, false) ],
                '{"one":100,"two":(test) => {Math.random() * test}}'
            ],
            'function arrow inline' => [
                [ 'one' => 100, 'two' => new JsFunction(['test'], 'Math.random() * test', true, true) ],
                '{"one":100,"two":(test) => (Math.random() * test)}'
            ],
            'function standard' => [
                [ 'one' => 100, 'two' => new JsFunction(['test'], 'Math.random() * test', false, false) ],
                '{"one":100,"two":function(test) {Math.random() * test}}'
            ],
            'function standard inline' => [
                [ 'one' => 100, 'two' => new JsFunction(['test'], 'Math.random() * test', false, true) ],
                '{"one":100,"two":function(test) {return (Math.random() * test);}}'
            ],
            'date relative (day)' => [
                [ 'one' => 200, 'two' => new JsDate('+1 day') ],
                '{"one":200,"two":new Date(+new Date + 86400000)}'
            ],
            'date relative (month)' => [
                [ 'one' => 300, 'two' => new JsDate('+3 month') ],
                '{"one":300,"two":new Date(+new Date + 7776000000)}'
            ],
            'date relative (year)' => [
                [ 'one' => 400, 'two' => new JsDate('+1 year') ],
                '{"one":400,"two":new Date(+new Date + 31536000000)}'
            ],
            'date relative (negative)' => [
                [ 'one' => 400, 'two' => new JsDate('-5 days -3 hours') ],
                '{"one":400,"two":new Date(+new Date - 442800000)}'
            ],
            'date absolute' => [
                [ 'one' => 500, 'two' => new JsDate('2026-08-01') ],
                '{"one":500,"two":new Date("2026-08-01")}'
            ],
        ];
    }


    /** @dataProvider dataJsExpression */
    public function testJsExpression(array $input, string $expected)
    {
        $actual = Js::encode($input, false);
        $this->assertEquals($expected, $actual);

        $actual = Js::encode($input, true);
        $expected = "Object.freeze({$expected})";
        $this->assertEquals($expected, $actual);
    }
}
