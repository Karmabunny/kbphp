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

    public function test() {

        $thingo = new Thingo([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'complex' => [ 5, 5, 5 ],
        ]);

        $array = $thingo->toArray();
        $iterator = $thingo->getIterator();

        $this->assertEquals(true, $iterator->valid());

        $this->assertEquals(1, $thingo->id);
        $this->assertEquals(1, $thingo['id']);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals(1, $iterator->current());

        $thingo->id = 999;
        $this->assertEquals(999, $thingo->id);
        $this->assertEquals(999, $thingo['id']);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals(999, $iterator->current());

        $iterator->next();
        $this->assertEquals(true, $iterator->valid());

        $this->assertEquals(123, $thingo->parent_id);
        $this->assertEquals(123, $thingo['parent_id']);
        $this->assertEquals(123, $array['parent_id']);
        $this->assertEquals(123, $iterator->current());

        $iterator->next();
        $this->assertEquals(true, $iterator->valid());

        $this->assertEquals(null, $thingo->name);
        $this->assertEquals(null, $thingo['name']);
        $this->assertEquals(null, $array['name']);
        $this->assertEquals(null, $iterator->current());

        $iterator->next();
        $this->assertEquals(true, $iterator->valid());

        $this->assertEquals('blah blah blah', $thingo->description);
        $this->assertEquals('blah blah blah', $thingo['description']);
        $this->assertEquals('blah blah blah', $array['description']);
        $this->assertEquals('blah blah blah', $iterator->current());

        $iterator->next();
        $this->assertEquals(true, $iterator->valid());

        $this->assertEquals([5, 5, 5], $thingo->complex);
        $this->assertEquals([5, 5, 5], $thingo['complex']);
        $this->assertEquals([5, 5, 5], $array['complex']);
        $this->assertEquals([5, 5, 5], $iterator->current());

        $iterator->next();
        $this->assertEquals(false, $iterator->valid());
        $this->assertEquals(null, $iterator->current());

        $iterator->rewind();
        $this->assertEquals(true, $iterator->valid());
        $this->assertEquals(999, $iterator->current());
    }

}

class Thingo extends Collection {

    /** @var int */
    public $id = 1;

    /** @var int */
    public $parent_id = 1;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var array */
    public $complex = [];
}
