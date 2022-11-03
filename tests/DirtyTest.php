<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\DataObject;
use karmabunny\kb\DirtyPropertiesTrait;
use PHPUnit\Framework\TestCase;


/**
 * Test the dirty properties trait.
 */
final class DirtyTest extends TestCase
{

    public function testDirty() {
        $thingo = new ThingoDirty([
            'parent_id' => 123,
            'description' => 'blah blah blah',
            'empty' => null,
        ]);

        // No changes - no dirty properties.
        $actual = $thingo->getDirtyProperties();
        $expected = [];
        $this->assertEquals($expected, $actual);

        $thingo->description = 'neat!';

        $actual = $thingo->getDirtyProperties();
        $expected = ['description' => 'neat!'];
        $this->assertEquals($expected, $actual);

        $thingo->update([
            'empty' => 'not empty',
            'default' => 'xyz',
        ]);

        $actual = $thingo->getDirtyProperties();
        $expected = [
            'description' => 'neat!',
            'empty' => 'not empty',
            'default' => 'xyz',
        ];
        $this->assertEquals($expected, $actual);

        // Record fresh checksums.
        $thingo->getChecksums()->update();

        // Empty again!
        $actual = $thingo->getDirtyProperties();
        $expected = [];
        $this->assertEquals($expected, $actual);
    }
}


class ThingoDirty extends DataObject
{
    use DirtyPropertiesTrait;

    public $parent_id;

    public $description;

    public $empty;

    public $default = 'abc';


    public function update($config)
    {
        parent::update($config);
        $this->getChecksums();
    }


    public function getDirtyProperties(): array
    {
        return $this->getChecksums()->getAllDirty();
    }
}
