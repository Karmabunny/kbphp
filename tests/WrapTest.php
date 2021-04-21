<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

use karmabunny\kb\Wrap;
use PHPUnit\Framework\TestCase;

/**
 * Test the Wrap helper.
 */
final class WrapTest extends TestCase
{

    public function getData()
    {
        return [
            new AnotherThing(true, 'one'),
            new AnotherThing(false, 'two'),
            new AnotherThing(true, 'three'),
            new AnotherThing(false, 'four'),
            null,
        ];
    }


    public function testProperty()
    {
        $items = $this->getData();

        $expected = ['one', 'two', 'three', 'four', null];
        $actual = array_map(Wrap::property('name'), $items);

        $this->assertEquals($expected, $actual);
    }


    public function testMethod()
    {
        $items = $this->getData();

        $expected = [1 => $items[1], 2 => $items[2]];
        $actual = array_filter($items, Wrap::method('contains', 't'));

        $this->assertEquals($expected, $actual);
        // print_r($actual);
    }


    public function testItem()
    {
        $items = $this->getData();
        $items = array_map(Wrap::method('toArray'), $items);

        $expected = [true, false, true, false, null];
        $actual = array_map(Wrap::item('active'), $items);

        $this->assertEquals($expected, $actual);
    }
}


class AnotherThing
{
    public $active;

    public $name;

    public function __construct($active, $name)
    {
        $this->active = $active;
        $this->name = $name;
    }

    public function contains(string $letter)
    {
        return strpos($this->name, $letter) !== false;
    }

    public function toArray()
    {
        return [
            'active' => $this->active,
            'name' => $this->name,
        ];
    }
}
