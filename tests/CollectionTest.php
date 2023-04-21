<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\ArrayableFields;
use karmabunny\kb\UpdateStrictTrait;
use karmabunny\kb\UpdateTidyTrait;
use karmabunny\kb\UpdateVirtualTrait;
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


    public function testArrayableFields() {
        $thingo = new ThingoFields([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'empty' => null,
        ]);

        $array = $thingo->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(123, $array['parent_id']);
        $this->assertEquals('blah blah blah', $array['description']);
        $this->assertArrayNotHasKey('key', $array);

        $this->assertEquals($thingo->getVirtualThing(), $array['thing']);

        $array = $thingo->toArray(['id', 'description']);

        $this->assertArrayNotHasKey('empty', $array);
        $this->assertArrayNotHasKey('name', $array);
        $this->assertArrayNotHasKey('key', $array);

        $array = $thingo->toArray(null, ['more_things']);

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('parent_id', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('thing', $array);
        $this->assertArrayHasKey('more_things', $array);
        $this->assertArrayNotHasKey('empty', $array);
        $this->assertArrayNotHasKey('key', $array);

        $expected = ['a', 'b', 'c'];
        $this->assertEquals($expected, $array['more_things']);
    }


    public function testIterator() {
        $thingo = new Thingo([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'empty' => null,
        ]);

        /** @var Iterator */
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
        // print_r($str);

        // 2. Change it again before hydrating.
        Thingo::$common = 888;

        $other = unserialize($str);

        // 1. The private secret resets, protected is lives through serialize.
        $this->assertEquals(['a secret', 'nope'], $other->getSecrets());

        // 2. The static var isn't modified by unserialize.
        $this->assertEquals(888, Thingo::$common);

        // Regular properties are passed through properly.
        $this->assertEquals($thingo->id, $other->id);
        $this->assertEquals($thingo->parent_id, $other->parent_id);
        $this->assertEquals($thingo->empty, $other->empty);
    }


    public function testUpdateModifiers()
    {
        // Standard behaviour.
        $thingo = new Thingo([
            'name' => 'good',
            'bad_thing' => 'bad',
        ]);

        $this->assertEquals('good', $thingo->name);
        $this->assertEquals('bad', $thingo->bad_thing);

        // Tidy mode - no unknown properties, but you, quietly.
        $other = new ThingoTidy([
            'name' => 'good',
            'bad_thing' => 'bad',
        ]);

        $this->assertEquals('good', $other->name);

        // Errors occur here instead.
        $value = @$other->bad_thing;
        $error = error_get_last();

        $this->assertNull($value);
        $this->assertStringContainsString('bad_thing', $error['message']);

        // Strict mode - errors are immediate.
        try {
            $another = new ThingoStrict([
                'name' => 'good',
                'bad_thing' => 'bad',
                'more_bad' => 'ah geez',
            ]);
            $this->fail('Constructor should throw.');
        }
        catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('bad_thing', $exception->getMessage());
            $this->assertStringContainsString('more_bad', $exception->getMessage());
        }
    }


    public function testVirtualSetters()
    {
        $thing = new ThingoVirtual([
            'name' => 'good',
            'thing' => 'here',
        ]);

        $this->assertEquals('OH LOOK - here', $thing->thing);

        $thing = new ThingoTidy([
            'name' => 'good',
            'thing' => 'here',
        ]);

        $this->assertEquals('HEY - here', $thing->thing);

        $thing = new ThingoStrict([
            'name' => 'good',
            'thing' => 'here',
        ]);

        $this->assertEquals('NEAT - here', $thing->thing);
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


class ThingoFields extends Thingo
    implements ArrayableFields
{

    /** @var string */
    public $key = 'kinda-secret';


    public function fields(): array
    {
        $fields = Collection::fields();
        $fields['thing'] = [$this, 'getVirtualThing'];
        $fields['key'] = false;
        return $fields;
    }


    public function extraFields(): array
    {
        return [
            'more_things' => function() {
                return ['a', 'b', 'c'];
            },
        ];
    }
}


class ThingoTidy extends Collection {
    use UpdateTidyTrait;
    use UpdateVirtualTrait;

    public $name;

    public $thing;

    public function virtual(): array
    {
        return [
            'thing' => [$this, 'setThing'],
        ];
    }

    public function setThing($thing)
    {
        if ($thing === null) return;
        $this->thing = 'HEY - ' . $thing;
    }
}

class ThingoStrict extends Collection {
    use UpdateStrictTrait;
    use UpdateVirtualTrait;

    public $name;

    public $thing;

    public function virtual(): array
    {
        return [
            'thing' => [$this, 'setThing'],
        ];
    }

    public function setThing($thing)
    {
        if ($thing === null) return;
        $this->thing = 'NEAT - ' . $thing;
    }
}


class ThingoVirtual extends Collection {
    use UpdateVirtualTrait;

    public $name;

    public $thing;

    public function virtual(): array
    {
        return [
            'thing' => [$this, 'setThing'],
        ];
    }

    public function setThing($thing)
    {
        if ($thing === null) return;
        $this->thing = 'OH LOOK - ' . $thing;
    }
}