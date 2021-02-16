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

        // Modifying keys.
        $actual = Arrays::fill(4, function(&$key) {
            $value = 2 ** $key;
            $key = 'abc' . ($key + 1);
            return $value;
        });

        $expected = [
            'abc1' => 1,
            'abc2' => 2,
            'abc3' => 4,
            'abc4' => 8,
        ];

        $this->assertEquals($expected, $actual);

        // A more realistic use case.
        $actual = Arrays::fill(7, function(&$i) {
            $i += 1;
            return null;
        });
        $expected = [
            1 => null,
            2 => null,
            3 => null,
            4 => null,
            5 => null,
            6 => null,
            7 => null,
        ];

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


    public function testIsNumeric()
    {
        $this->assertTrue(Arrays::isNumeric([
            'abc',
            '123',
        ]));

        $this->assertFalse(Arrays::isNumeric([
            'abc' => 'def',
            123 => '567',
            'thing',
        ]));
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


    public function testGetValue()
    {
        $array = [
            'one' => [
                [
                    'id' => 123,
                    'name' => 'abc'
                ],
                [
                    'id' => 456,
                    'name' => 'def',
                    'property' => [
                        'oh',
                        'cool',
                        'stuff',
                    ],
                ]
            ],
            'two' => 'neat!'
        ];

        $actual = Arrays::getValue($array, 'one');
        $expected = $array['one'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::getValue($array, 'one.id');
        $expected = [123, 456];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::getValue($array, 'one.name');
        $expected = ['abc', 'def'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::getValue($array, 'one.property');
        $expected = [null, ['oh', 'cool', 'stuff']];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::getValue($array, 'two');
        $expected = $array['two'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::getValue($array, 'three');
        $this->assertNull($actual);
    }


    public function testCreateMap()
    {
        $objects = [
            (object)[ 'id' => 111, 'name' => 'one' ],
            (object)[ 'id' => 222, 'name' => 'two' ],
            (object)[ 'id' => 333, 'name' => 'three' ],
            (object)[ 'id' => 100, 'name' => 'four' ],
            (object)[ 'id' => 111, 'name' => 'five' ],
        ];

        $actual = Arrays::createMap($objects, 'id', 'name');
        $expected = [
            111 => 'five',
            222 => 'two',
            333 => 'three',
            100 => 'four',
        ];

        $this->assertEquals($expected, $actual);


        $arrays = [
            [ 'id' => 111, 'name' => 'one' ],
            'not an object',
            [ 'id' => 222, 'name' => null ],
            (object)[ 'name' => 'missing key' ],
            [ 'ID' => 100, 'name' => 'misspelled key' ],
            [ 'id' => 333, 'name' => 'hello?' ],
        ];

        $actual = Arrays::createMap($arrays, 'id', 'name');
        $expected = [
            111 => 'one',
            222 => null,
            333 => 'hello?',
        ];

        $this->assertEquals($expected, $actual);
    }
}
