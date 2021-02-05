<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Test the Collection class.
 */
final class CollectionTest extends TestCase {

    public function testProperties() {
        $thingo = new Thingo([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'empty' => null,
        ]);


        // Default value is 1.
        $this->assertEquals(1, $thingo->id);
        $this->assertEquals(1, $thingo['id']);

        // Modify it.
        $thingo->id = 999;
        $this->assertEquals(999, $thingo->id);
        $this->assertEquals(999, $thingo['id']);

        // Set by constructor, default is 0.
        $this->assertEquals(123, $thingo->parent_id);
        $this->assertEquals(123, $thingo['parent_id']);

        // This exists, but is null implicitly (unset).
        $this->assertEquals(null, $thingo->name);
        $this->assertEquals(null, $thingo['name']);

        // This is default unset/null.
        $this->assertEquals('blah blah blah', $thingo->description);
        $this->assertEquals('blah blah blah', $thingo['description']);

        // This is explicitly (constructor) set to null.
        $this->assertEquals(null, $thingo->empty);
        $this->assertEquals(null, $thingo['empty']);
    }


    public function testArray() {
        $thingo = new Thingo([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'empty' => null,
        ]);

        $array = $thingo->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(123, $array['parent_id']);
        $this->assertEquals('blah blah blah', $array['description']);

        $this->assertArrayNotHasKey('empty', $array);
        $this->assertArrayNotHasKey('name', $array);

        $this->assertEquals($thingo->getVirtualThing(), $array['thing']);
    }


    public function testIterator() {
        $thingo = new Thingo([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'empty' => null,
        ]);

        $iterator = $thingo->getIterator();

        $this->assertEquals(true, $iterator->valid());

        $items = [];

        foreach ($iterator as $key => $value) {
            $items[] = [ $key, $value ];
        }

        // After iterating, it should be depleted.
        $this->assertEquals(false, $iterator->valid());
        $this->assertEquals(null, $iterator->current());

        // But we can rewind it of course.
        $iterator->rewind();
        $this->assertEquals(true, $iterator->valid());
        $this->assertEquals(1, $iterator->current());

        // All the items match too.
        $expected = [
            ['id', 1],
            ['parent_id', 123],
            ['name', null],
            ['description', 'blah blah blah'],
            ['empty', null],
        ];

        $this->assertEquals($expected, $items);
    }


    public function testSerialize()
    {
        $thingo = new Thingo([
            'parent_id' => 123,
            'empty' => null,
        ]);

        // 1. Set a secret.
        $thingo->setSecret('nope');
        $this->assertEquals(['nope', 'nope'], $thingo->getSecrets());

        // 2. Change a static var.
        Thingo::$common = 777;

        $str = serialize($thingo);
        print_r($str);

        // 2. Change it again before hydrating.
        Thingo::$common = 888;

        $other = unserialize($str);

        // 1. The secret resets - doesn't pass through serialisation.
        $this->assertEquals(['a secret', 'okay'], $other->getSecrets());

        // 2. The static var isn't modified by unserialize.
        $this->assertEquals(888, Thingo::$common);

        // Regular properties are passed through properly.
        $this->assertEquals($thingo->id, $other->id);
        $this->assertEquals($thingo->parent_id, $other->parent_id);
        $this->assertEquals($thingo->empty, $other->empty);
    }
}

class Thingo extends Collection {

    public static $common = 555;

    private $_shh = 'a secret';

    protected $_quiet = 'okay';

    /** @var int */
    public $id = 1;

    /** @var int */
    public $parent_id = 0;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var array */
    public $empty = [];


    public function fields(): array
    {
        return [
            'thing' => [$this, 'getVirtualThing'],
        ];
    }

    public function getVirtualThing()
    {
        return '1234567890';
    }

    public function getSecrets()
    {
        return [$this->_shh, $this->_quiet];
    }

    public function setSecret(string $value)
    {
        $this->_shh = $value;
        $this->_quiet = $value;
    }
}
