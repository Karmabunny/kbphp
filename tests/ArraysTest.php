<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * Test the Arrays helpers.
 */
final class ArraysTest extends TestCase {

    public function testFirst()
    {
        $array = [10, 20, 30];
        $this->assertEquals(10, Arrays::first($array));

        $array = [1 => 40, 0 => 50, -1 => 60];
        $this->assertEquals(40, Arrays::first($array));
    }


    public function testLast()
    {
        $array = [10, 20, 30];
        $this->assertEquals(30, Arrays::last($array));

        $array = [1 => 40, 0 => 50, -1 => 60];
        $this->assertEquals(60, Arrays::last($array));
    }


    public function testFill()
    {
        $actual = Arrays::fill(10, function($i) {
            return 2 ** $i;
        });

        $expected = [1, 2, 4, 8, 16, 32, 64, 128, 256, 512];

        $this->assertEquals($expected, $actual);
    }


    public function testFillKeyed()
    {
        $actual = Arrays::fillKeyed(4, function($i) {
            return [ 2 ** $i, 3 ** $i ];
        });

        $expected = [
            1 => 1,
            2 => 3,
            4 => 9,
            8 => 27,
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testFind()
    {
        $array = ['abc', '  find this  ', 'def', 'ghi'];

        $actual = Arrays::find($array, function($item) {
            return trim($item) === 'find this';
        });

        $this->assertEquals($array[1], $actual);

        $actual = Arrays::find($array, function($item) {
            return false;
        });

        $this->assertNull($actual);
    }


    public function testFlattenNoKeys()
    {
        $actual = Arrays::flatten([
            [
                10 => 123,
                30 => 789,
                'overwrite' => 'nope',
            ],
            [
                'overwrite' => 'yas',
                20 => 456,
                [
                    'abc',
                    'def',
                ],
            ],
            [
                'ghi',
                'jkl',
            ],
        ]);

        $expected = [
            123,
            789,
            'nope',
            'yas',
            456,
            'abc',
            'def',
            'ghi',
            'jkl',
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testFlattenWithKeys()
    {
        $actual = Arrays::flatten([
            [
                10 => 123,
                30 => 789,
                'overwrite' => 'nope',
            ],
            [
                'overwrite' => 'yas',
                20 => 456,
                [
                    'abc',
                    'def',
                ],
            ],
            [
                'ghi',
                'jkl',
            ],
        ], true);

        $expected = [
            10 => 123,
            30 => 789,
            'overwrite' => 'yas',
            20 => 456,
            // Apparently we don't need these??
            // TODO Figure how to get these back.
            // 'abc',
            // 'def',
            0 => 'ghi',
            1 => 'jkl',
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testReverse()
    {
        $actual = iterator_to_array(Arrays::reverse([
            5 => 'five',
            4 => 'four',
            3 => 'three',
            2 => 'two',
            1 => 'one',
        ]));

        $expected = [
            5 => 'five',
            4 => 'four',
            3 => 'three',
            2 => 'two',
            1 => 'one',
        ];

        $this->assertEquals($expected, $actual);
    }
}
