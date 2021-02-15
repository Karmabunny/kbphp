<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\Log;
use PHPUnit\Framework\TestCase;

/**
 * Test log stringify
 **/
class LogTest extends TestCase
{
    public function testStringify()
    {
        $thing = [
            new Test1([
                'array' => [
                    new Test1(['thing' => 'one']),
                    new Test1(['thing' => 'two']),
                ],
                'object' => (object) [
                    'thing' => 'three',
                ],
            ]),
            new Test1([
                'thing' => 'four',
                'array' => [1, 2, 'three' => [4, 5, 6]],
            ]),
        ];

        // die(Log::stringify($thing) . PHP_EOL);

        $actual = Log::stringify($thing);
        $expected = trim(<<<'EOF'
        [0]: Test1
          thing: "abc"
          array:
            [0]: Test1
              thing: "one"
              array: []
              object: NULL
              float: 1.234
              virtual: "count - 1"
            [1]: Test1
              thing: "two"
              array: []
              object: NULL
              float: 1.234
              virtual: "count - 2"
          object: stdClass
            thing: "three"
          float: 1.234
          virtual: "count - 3"
        [1]: Test1
          thing: "four"
          array:
            [0]: 1
            [1]: 2
            [three]:
              [0]: 4
              [1]: 5
              [2]: 6
          object: NULL
          float: 1.234
          virtual: "count - 4"
        EOF);

        $this->assertEquals($expected, $actual);
    }
}


class Test1 extends Collection
{
    public $thing = 'abc';

    public $array = [];

    public $object = null;

    public $float = 1.234;

    protected $secret = '$2y$10$';

    public static $count = 0;

    public function fields(): array
    {
        return [
            'virtual' => function() {
                return 'count - ' . ++self::$count;
            },
        ];
    }
}
