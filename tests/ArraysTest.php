<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Arrays;
use karmabunny\kb\Sortable;
use karmabunny\kb\SortFieldsTrait;
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


    public function testFillIntersectionKeys()
    {
        $keys = ['a', 'b', 'c'];
        $config = ['a' => 100, 'x' => 200, 'z' => 300];

        $actual = Arrays::fillIntersectionKeys($keys, $config);
        $expected = ['a' => 100, 'b' => null, 'c' => null];

        $this->assertEquals($expected, $actual);

        $actual = Arrays::fillIntersectionKeys($keys, $config, true);
        $expected = ['a' => 100, 'b' => true, 'c' => true];

        $this->assertEquals($expected, $actual);
    }


    public function testImplodeWithKeys()
    {
        $array = [
            'a' => 1,
            'c' => 3,
            'b' => 2,
        ];

        $actual = Arrays::implodeWithKeys($array);
        $expected = 'a1c3b2';

        $this->assertEquals($expected, $actual);

        $actual = Arrays::implodeWithKeys($array, ' ');
        $expected = 'a1 c3 b2';

        $this->assertEquals($expected, $actual);

        $actual = Arrays::implodeWithKeys($array, ', ', ': ');
        $expected = 'a: 1, c: 3, b: 2';

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


    public function testFindKey()
    {
        $array = [
            'abc',
            'found' => '  find this  ',
            'not this' => 'def',
            'ghi',
        ];

        $actual = Arrays::findKey($array, function($item) {
            return trim($item) === 'find this';
        });

        $this->assertEquals('found', $actual);

        $actual = Arrays::findKey($array, function($item) {
            return false;
        });

        $this->assertNull($actual);
    }


    public function testIndexOf()
    {
        $array = [
            'abc',
            'found' => '  find this  ',
            'this one' => 'def',
            'ghi',
        ];

        $actual = Arrays::indexOf($array, '0');
        $this->assertEquals(0, $actual);

        $actual = Arrays::indexOf($array, 'found');
        $this->assertEquals(1, $actual);

        $actual = Arrays::indexOf($array, 'this one');
        $this->assertEquals(2, $actual);

        $actual = Arrays::indexOf($array, '1');
        $this->assertEquals(3, $actual);

        $actual = Arrays::indexOf($array, 'not this');
        $this->assertNull($actual);
    }


    public function testReduce()
    {
        $array = [2 => false, 5 => true, 10 => false, 15 => true];

        $actual = Arrays::reduce($array, function($sum, $item, $key) {
            if ($item) return $sum + $key;
            return $sum;
        }, 100);

        $this->assertEquals(120, $actual);
    }


    public function testFilterRecursive()
    {
        $array = [
            'item1' => [
                'nested1' => 123,
                'nested2' => 0,
                'nested3' => null,
                'nested4' => 567,
                'deeper' => [
                    'deepest' => 'abc',
                ],
            ],
            'item2' => [],
            'item3' => [
                'empty1' => null,
                'empty2' => null,
            ],
        ];

        // Standard filter, discard arrays.
        $expected = [
            'item1' => [
                'nested1' => 123,
                'nested4' => 567,
                'deeper' => [
                    'deepest' => 'abc',
                ],
            ],
        ];

        $actual = Arrays::filterRecursive($array);
        $this->assertEquals($expected, $actual);

        // Standard filter, keep arrays.
        $expected = [
            'item1' => [
                'nested1' => 123,
                'nested4' => 567,
                'deeper' => [
                    'deepest' => 'abc',
                ],
            ],
            'item2' => [],
            'item3' => [],
        ];

        $actual = Arrays::filterRecursive($array, null, Arrays::LEAVES_ONLY);
        $this->assertEquals($expected, $actual);

        // A filter that removes integers.
        $expected = [
            'item1' => [
                'nested3' => null,
                'deeper' => [
                    'deepest' => 'abc',
                ],
            ],
            'item2' => [],
            'item3' => [
                'empty1' => null,
                'empty2' => null,
            ],
        ];

        $actual = Arrays::filterRecursive($array, function($item) {
            return !is_int($item);
        });

        $this->assertEquals($expected, $actual);

        // A filter that remove the 'deeper' array, requires self-first.
        $expected = [
            'item1' => [
                'nested1' => 123,
                'nested2' => 0,
                'nested3' => null,
                'nested4' => 567,
                'deeper' => [],
            ],
            'item2' => [],
            'item3' => [
                'empty1' => null,
                'empty2' => null,
            ],
        ];

        $filter = function($item, $key) {
            return $key !== 'deeper';
        };

        // Test with leaves-only, matches original.
        $actual = Arrays::filterRecursive($array, $filter, Arrays::LEAVES_ONLY);
        $this->assertEquals($array, $actual);

        // Now test with self-first.
        $actual = Arrays::filterRecursive($array, $filter, Arrays::SELF_FIRST);
        $this->assertEquals($expected, $actual);

        // Also with child-first, for good luck.
        $actual = Arrays::filterRecursive($array, $filter, Arrays::CHILD_FIRST);
        $this->assertEquals($expected, $actual);

        // Combine both discard + self-first.
        unset($expected['item1']['deeper']);
        unset($expected['item2']);
        $actual = Arrays::filterRecursive($array, $filter, Arrays::SELF_FIRST | Arrays::DISCARD_EMPTY_ARRAYS);
        $this->assertEquals($expected, $actual);
    }


    public function testFilterKeys()
    {
        $array = [
            'aaa' => 123,
            'xxx' => 567,
            'zzz' => 789,
            '111' => null,
        ];

        // Standard filter.
        $expected = [
            'aaa' => 123,
            'zzz' => 789,
        ];
        $actual = Arrays::filterKeys($array, ['aaa', 'zzz', 'bbb']);
        $this->assertEquals($expected, $actual);

        // Fill keys.
        // This also tests that the '111' doesn't come through just because it's null.
        // It's _not_ in the filter but PHP sometimes has trouble telling the difference.
        $expected = [
            'aaa' => 123,
            'zzz' => 789,
            'bbb' => null,
        ];
        $actual = Arrays::filterKeys($array, ['aaa', 'zzz', 'bbb'], true);
        $this->assertEquals($expected, $actual);
    }


    public function testZip()
    {
        $arrays = [
            [1,2],
            [4,5,6],
            [7,8,9],
        ];

        $expected = [
            [1,4,7],
            [2,5,8],
            [null,6,9],
        ];

        // Builtin behaviour.
        $actual = array_map(null, ...$arrays);
        $this->assertEquals($expected, $actual);

        // We match that.
        $actual = Arrays::zip(...$arrays);
        $this->assertEquals($expected, $actual);

        $iterables = [
            (function() {
                yield 1;
                yield 2;
            })(),
            (function() {
                yield 4;
                yield 5;
                yield 6;
            })(),
            (function() {
                yield 7;
                yield 8;
                yield 9;
            })(),
        ];

        $actual = Arrays::zip(...$iterables);
        $this->assertEquals($expected, $actual);
    }


    public function testMapKeys()
    {
        $array = [
            'aaa' => 123,
            'xxx' => 567,
            'zzz' => 789,
        ];

        $expected = [
            'prefix_aaa' => 'aaa123',
            'prefix_xxx' => 'xxx567',
            'prefix_zzz' => 'zzz789',
        ];

        $actual = Arrays::mapKeys($array, function($item, $key) {
            return ['prefix_' . $key, $key . $item];
        });

        $this->assertEquals($expected, $actual);
    }



    public function testMapWithKeys()
    {
        $array = [
            'aaa' => 123,
            'xxx' => 567,
            'zzz' => 789,
        ];

        $expected = [
            'prefix_aaa' => 'aaa123',
            'prefix_xxx' => 'xxx567',
            'prefix_zzz' => 'zzz789',
        ];

        $actual = Arrays::mapWithKeys($array, function($item, &$key) {
            $item = $key . $item;
            $key = 'prefix_' . $key;
            return $item;
        });

        $this->assertEquals($expected, $actual);
    }


    public function testMapRecursive()
    {
        $array = [
            'aaa' => 123,
            'xxx' => 567,
            'zzz' => [
                'abc',
                'def',
                'ghi' => [
                    1,
                    2,
                    3,
                    'xyz' => 'hello',
                    'zzz' => ['world'],
                ],
            ],
        ];

        // leaves are [] if the key is numeric, otherwise ()
        // arrays (self-first) the 'ghi' key is redacted
        // arrays (child-first) gain additional children
        $filter = function($item, $key) {
            if (is_array($item)) {
                if ($key === 'ghi') {
                    return 'REDACTED';
                }
                else {
                    $item[] = 'ADDED';
                    return $item;
                }
            }
            else if (is_numeric($key)) {
                return '[' . $item . ']';
            }
            else {
                return '(' . $item . ')';
            }
        };

        // LEAVES_ONLY
        $expected = [
            'aaa' => '(123)',
            'xxx' => '(567)',
            'zzz' => [
                '[abc]',
                '[def]',
                'ghi' => [
                    '[1]',
                    '[2]',
                    '[3]',
                    'xyz' => '(hello)',
                    'zzz' => ['[world]'],
                ],
            ],
        ];

        $actual = Arrays::mapRecursive($array, $filter);
        $this->assertEquals($expected, $actual);

        // SELF_FIRST
        $expected = [
            'aaa' => '(123)',
            'xxx' => '(567)',
            'zzz' => [
                '[abc]',
                '[def]',
                'ghi' => 'REDACTED',
                '[ADDED]',
            ],
            '[ADDED]',
        ];

        $actual = Arrays::mapRecursive($array, $filter, Arrays::SELF_FIRST);
        $this->assertEquals($expected, $actual);

        // CHILD_FIRST
        $expected = [
            'aaa' => '(123)',
            'xxx' => '(567)',
            'zzz' => [
                '[abc]',
                '[def]',
                'ghi' => 'REDACTED',
                'ADDED',
            ],
            'ADDED',
        ];

        $actual = Arrays::mapRecursive($array, $filter, Arrays::CHILD_FIRST);
        $this->assertEquals($expected, $actual);
    }


    public function testShuffle()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 4, 5, 7, 8, 9, 10];

        $copy = array_merge($array);
        $this->assertSame($copy, $array);

        $actual = Arrays::shuffle($array, false);

        $this->assertTrue(Arrays::isNumeric($actual));
        $this->assertNotSame($array, $actual);
        $this->assertNotSame($array, array_values($actual));
        $this->assertSame($array, $copy);

        // There's a very slim chance that this test will fail.
        // By the nature of 'random' - it's possible that the shuffle will
        // result in an identical array.

        $actual = Arrays::shuffle($array, true);
        $this->assertTrue(Arrays::isAssociated($actual));
        $this->assertNotSame($array, $actual);
        $this->assertSame($array, $copy);
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


    public function testFlattenIterable()
    {
        $it = (function() {
            yield 123;
            yield (function() {
                yield 'abc';
                yield 'def';
                yield (function() {
                    yield 'how';
                    yield (function() {
                        yield 'deep';
                        yield from (function() {
                            yield 'can';
                            yield 'you';
                            yield 'go';
                        })();
                    })();
                })();
                yield 'ghi';
            })();
            yield 456;
        });

        $actual = Arrays::flatten($it());
        $expected = [
            123,
            'abc',
            'def',
            'how',
            'deep',
            'can',
            'you',
            'go',
            'ghi',
            456,
        ];

        $this->assertEquals($expected, $actual);

        // Test max depth.
        $actual = Arrays::flatten($it(), false, 3);
        $expected = [
            123,
            'abc',
            'def',
            'how',
            'ghi',
            456,
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


    public function testFlattenKeys()
    {
        $nested = [
            'root' => [
                'key1' => [
                    'deep' => 'value',
                    'hello' => 'world',
                ],
                'key2' => 'ok',
            ],
            'second' => 'neat',
            'third' => [ 'key1' => ['deep' => 'last'] ],
            'fourth' => [
                'thing' => [
                    'abc',
                    'def',
                    'ghi',
                    'oops' => [
                        'hi' => 'there',
                    ],
                ],
                'list' => [
                    [
                        'type' => 'object',
                        'name' => 'first',
                        'prop1' => 111,
                        'prop2' => 222,
                    ],
                    [
                        'type' => 'object',
                        'name' => 'second',
                        'prop1' => 333,
                        'prop2' => 444,
                    ],
                ],
            ],
        ];

        $expected = [
            'root.key1.deep' => 'value',
            'root.key1.hello' => 'world',
            'root.key2' => 'ok',
            'second' => 'neat',
            'third.key1.deep' => 'last',
            'fourth.thing' => $nested['fourth']['thing'],
            'fourth.thing.oops.hi' => 'there',
            'fourth.list' => $nested['fourth']['list'],
        ];

        $actual = Arrays::flattenKeys($nested);
        $this->assertEquals($expected, $actual);

        $nested = [
            'root' => [
                'key1' => [
                    'deep' => 'value',
                    'hello' => 'world',
                ],
                'key2' => 'ok',
            ],
        ];

        $expected = [
            'root/key1/deep' => 'value',
            'root/key1/hello' => 'world',
            'root/key2' => 'ok',
        ];

        $actual = Arrays::flattenKeys($nested, '/');
        $this->assertEquals($expected, $actual);
    }


    public function testExplodeKeys()
    {
        $array = [
            '' => 'value0',
            'root' => 'value1',
            'root/thing1' => 'value2',
            'root/thing2' => 'value3',
            'another/hello' => 'value4',
            'another/world' => 'value5',
            'one' => 'value6',
            'one/two' => 'value7',
            'one/two/three' => 'value8',
            'one/two/three/four' => 'value9',
            'one/two/three/five' => 'value10',
            'solo' => 'value11',
        ];

        $actual = Arrays::explodeKeys($array, '/', '_');

        $expected = [
            '_' => 'value0',
            'root' => [
                '_' => 'value1',
                'thing1' => 'value2',
                'thing2' => 'value3',
            ],
            'another' => [
                'hello' => 'value4',
                'world' => 'value5',
            ],
            'one' => [
                '_' => 'value6',
                'two' => [
                    '_' => 'value7',
                    'three' => [
                        '_' => 'value8',
                        'four' => 'value9',
                        'five' => 'value10',
                    ],
                ],
            ],
            'solo' => 'value11',
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testIsNumeric()
    {
        $this->assertTrue(Arrays::isNumeric([
            'abc',
            '123',
        ]));

        $this->assertTrue(Arrays::isNumeric([
            0 => 'abc',
            1 => '123',
        ]));

        $this->assertFalse(Arrays::isNumeric([
            0 => 'abc',
            5 => '123',
        ]));

        $this->assertFalse(Arrays::isNumeric([
            'abc' => 'def',
            123 => '567',
            'thing',
        ]));

        $this->assertFalse(Arrays::isNumeric([
            100 => 'def',
            123 => '567',
            200 => 'thing',
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
            'zero' => [
                [ 'a' ],
                [ 'b', 'c' ],
            ],
            'one' => [
                [
                    'id' => 123,
                    'name' => 'abc',
                    'nested' => [
                        'list' => [ 1, 2, 3 ],
                        'hello' => 'world',
                    ],
                ],
                [
                    'id' => 456,
                    'name' => 'def',
                    'nested' => [
                        'list' => [ 4, 5, 6, 7 ],
                        'hello' => 'sunshine',
                    ],
                    'property' => [
                        'oh',
                        'cool',
                        'stuff',
                    ],
                ],
                // Mixed numeric/associated keys.
                // This will only appear for a query: 'one.messy.*'
                // And not in: 'one.id' or 'one.name'
                'messy' => [
                    'id' => 789,
                    'name' => 'ghi',
                    'boy' => 'seems ok',
                    'list' => [[ 'flat' => [ 'ola' ] ]],
                    'not_flat' => [[[ 'hi' ]]],
                    'no_dice' => [[[ 'find' => 'me' ]]],
                ],
            ],
            'two' => 'neat!',
            'three' => null,
            'four' => [
                'connection' => [
                    'host' => 'abc.com',
                    'port' => 5060,
                    'options' => [],
                ],
            ],
        ];

        // Accessing root level arrays.
        $actual = Arrays::value($array, 'zero');
        $expected = $array['zero'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'one');
        $expected = $array['one'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'two');
        $expected = $array['two'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'three');
        $expected = $array['three'];
        $this->assertEquals($expected, $actual);


        // Non existing keys are null.
        $actual = Arrays::value($array, 'five');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'four.one');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'four.four');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'one.two');
        $this->assertNull($actual);


        // Get a deep nested value.
        $actual = Arrays::value($array, 'four.connection.host');
        $expected = 'abc.com';
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'four.connection.port');
        $expected = 5060;
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'four.connection.options');
        $expected = [];
        $this->assertEquals($expected, $actual);


        // Collect values from a numeric array.
        $actual = Arrays::value($array, 'one.id');
        $expected = [123, 456];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'one.name');
        $expected = ['abc', 'def'];
        $this->assertEquals($expected, $actual);


        // Collect a nested associated list.
        $actual = Arrays::value($array, 'one.nested');
        $expected = [
            ['list' => [1,2,3], 'hello' => 'world'],
            ['list' => [4,5,6,7], 'hello' => 'sunshine'],
        ];
        $this->assertEquals($expected, $actual);


        // Collect a nested numeric list.
        $actual = Arrays::value($array, 'one.nested.list');
        $expected = [ [1,2,3], [4,5,6,7] ];
        $this->assertEquals($expected, $actual);


        // This one only exists in the second item, but is easily accessed.
        // The result is flattened because there is only one result.
        $actual = Arrays::value($array, 'one.property');
        $expected = ['oh', 'cool', 'stuff'];
        $this->assertEquals($expected, $actual);


        // Mixed numeric/associated arrays.
        $actual = Arrays::value($array, 'one.messy.boy');
        $expected = 'seems ok';
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'one.boy');
        $this->assertNull($actual);


        // More flattening.
        $actual = Arrays::value($array, 'one.messy.list.flat');
        $expected = ['ola'];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'one.messy.not_flat');
        $expected = [[['hi']]];
        $this->assertEquals($expected, $actual);

        $actual = Arrays::value($array, 'one.messy.no_dice.find_me');
        $this->assertNull($actual);



        // Numeric keys aren't a thing.
        $actual = Arrays::value($array, 'one.0.property');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'one.1.property');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'zero.0');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'zero.1');
        $this->assertNull($actual);

        $actual = Arrays::value($array, 'one.nested.list.0');
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


    public function testNormalize()
    {
        // Typical mixed value/associated scenario.
        $options = [
            'f1',
            'f2' => 'DESC',
            'f3',
            'f4',
            'f5' => null,
        ];

        $actual = Arrays::normalizeOptions($options, 'ASC');
        $expected = [
            'f1' => 'ASC',
            'f2' => 'DESC',
            'f3' => 'ASC',
            'f4' => 'ASC',
            'f5' => null,
        ];
        $this->assertEquals($expected, $actual);

        // Just values.
        $options = [
            'f1',
            'f2',
            'f3',
        ];

        $actual = Arrays::normalizeOptions($options, 'DESC');
        $expected = [
            'f1' => 'DESC',
            'f2' => 'DESC',
            'f3' => 'DESC',
        ];
        $this->assertEquals($expected, $actual);

        // Just keyed.
        $options = [
            'f1' => 'ASC',
            'f2' => 'DESC',
            'f5' => null,
        ];

        $actual = Arrays::normalizeOptions($options, 'blah');
        $this->assertEquals($options, $actual);
    }


    public function testConfig()
    {
        $expected = [
            'abc' => 123,
            'def' => 456,
        ];

        // Traditional style
        $actual = Arrays::config(__DIR__ .'/config/valid-1.php');
        $this->assertEquals($expected, $actual);

        // Load it again
        $actual = Arrays::config(__DIR__ .'/config/valid-1.php');
        $this->assertEquals($expected, $actual);

        // Modern style
        $actual = Arrays::config(__DIR__ .'/config/valid-2.php');
        $this->assertEquals($expected, $actual);

        // Invalid
        $actual = Arrays::config(__DIR__ .'/config/invalid.php');
        $this->assertNull($actual);

        // Missing file
        $actual = Arrays::config(__DIR__ .'/config/valid-3.php');
        $this->assertNull($actual);

        // Test for leaky symbols.
        $this->assertFalse(isset($config));
        $this->assertTrue(function_exists('sillyGlobalFn'));
    }


    public function testSort()
    {
        // Basic items, default options (no keys, SORT_ASC).
        $items = [5, 6, 3.14, 100, '-100', '50.5', 0, -30.1];
        $expected = $items;
        sort($expected);

        $actual = Arrays::sorted($items);
        $this->assertEquals($expected, $actual);

        // Reversed.
        $expected = array_reverse($expected);
        $actual = Arrays::sorted($items, false, SORT_DESC);
        $this->assertEquals($expected, $actual);

        // Preserve keys.
        $items = ['a' => 'ghi', 'b' => 'abc', 'c' => 'def'];
        $expected = $items;
        asort($expected);

        $actual = Arrays::sorted($items, true, SORT_ASC);
        $this->assertEquals($expected, $actual);

        // Preserve keys, reversed.
        $expected = array_reverse($expected);

        $actual = Arrays::sorted($items, true, SORT_DESC);
        $this->assertEquals($expected, $actual);

        // Monoculture items.
        $items = [
            new SortItem1(100),
            new SortItem1(-100),
            new SortItem1(50),
            new SortItem1(30),
        ];

        $expected = [
            $items[1],
            $items[3],
            $items[2],
            $items[0],
        ];

        $actual = Arrays::sorted($items);
        $this->assertEquals($expected, $actual);

        // Polyculture objects.
        $items = [
            new SortItem1(100),  // 0 - 6
            new SortItem1(30),   // 1 - 3
            new SortItem2(70),   // 2 - 5
            new SortItem1(50),   // 3 - 4
            new SortItem2(700),  // 4 - 7
            new SortItem2(-70),  // 5 - 1
            new SortItem1(-100), // 6 - 0
            new SortItem2(20),   // 7 - 2
        ];

        $expected = [
            $items[6],
            $items[5],
            $items[7],
            $items[1],
            $items[3],
            $items[2],
            $items[0],
            $items[4],
        ];

        $actual = Arrays::sorted($items);
        $this->assertEquals($expected, $actual);

        // Just anything really. Unsortable objects retain their position.
        $items = [
            new SortItem1(100),     // 0  - 6
            -200,                   // 1  - 11
            new SortItem1(30),      // 2  - 3
            new SortItem2(70),      // 3  - 5
            200,                    // 4  - 14
            new SortItem1(50),      // 5  - 4
            new SortItem3(1000),    // 6  - 8
            new SortItem2(700),     // 7  - 7
            30,                     // 8  - 12
            new SortItem2(-70),     // 9  - 1
            60,                     // 10 - 13
            new SortItem1(-100),    // 11 - 0
            new SortItem3(-8000),   // 12 - 9
            null,                   // 13 - 10
            new SortItem2(20),      // 14 - 2
        ];

        $expected = [
            $items[11],
            $items[9],
            $items[14],
            $items[2],
            $items[5],
            $items[3],
            $items[0],
            $items[7],
            $items[13], // null
            $items[1],
            $items[8],
            $items[10],
            $items[4],
            $items[6],  // SortItem3 1000
            $items[12], // SortItem3 -8000
        ];

        $actual = Arrays::sorted($items);
        $this->assertEquals($expected, $actual);
    }


    public function testMultisort()
    {
        $items = [
            new SortFields(100, 'two'),
            new SortFields(100, 'one'),
            new SortFields(50, 'one'),
            new SortFields(30, 'two'),
        ];

        // No modes - no sort.
        $sort = Arrays::createMultisort([]);
        $actual = $items;
        usort($actual, $sort);

        $expected = [
            $items[0],
            $items[1],
            $items[2],
            $items[3],
        ];

        $this->assertEquals($expected, $actual);

        // Default sort (id).
        $sort = Arrays::createMultisort(['default']);
        $actual = $items;
        usort($actual, $sort);

        $expected = [
            $items[3],
            $items[2],
            $items[0],
            $items[1],
        ];

        $this->assertEquals($expected, $actual);

        // group sorts with default dir.
        $sort = Arrays::createMultisort(['group', 'id']);
        $actual = $items;
        usort($actual, $sort);

        $expected = [
            $items[2],
            $items[1],
            $items[3],
            $items[0],
        ];

        $this->assertEquals($expected, $actual);

        // group sorts witrh explicit dir.
        $sort = Arrays::createMultisort(['group' => SORT_DESC, 'id']);
        $actual = $items;
        usort($actual, $sort);

        $expected = [
            $items[3],
            $items[0],
            $items[2],
            $items[1],
        ];

        $this->assertEquals($expected, $actual);
    }
}


// A base class to ensure natural object ordering doesn't interfere.
abstract class SortBase
{
    public $id;
    public $group;
    public $rand;

    public function __construct($id, $group = 'default')
    {
        $this->id = $id;
        $this->group = $group;
        $this->rand = random_int(0, 1000 * 1000);
    }
}

// This is only sortable against itself.
class SortItem1 extends SortBase implements Sortable
{
    public function compare($other, $mode = 'default'): int
    {
        if ($other instanceof self) {
            return $this->id <=> $other->id;
        }

        return -1;
    }
}

// This sorts along with item 1.
class SortItem2 extends SortItem1
{
}

// This isn't sortable.
class SortItem3 extends SortBase
{
}

// Field sorter.
class SortFields extends SortBase implements Sortable
{
    use SortFieldsTrait;

    public function getSortKey(): string
    {
        return $this->id;
    }
}
