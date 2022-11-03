<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

// namespace Tests;

use karmabunny\kb\Reflect;
use PHPUnit\Framework\TestCase;

/**
 * Test the Reflect helper class.
 */
final class ReflectTest extends TestCase
{
    public function testLoadClasses()
    {
        $classes = Reflect::loadAllClasses([__DIR__ . '/Test']);
        $classes = iterator_to_array($classes);

        $this->assertEquals(3, count($classes));

        $expected = [
            'Test\BaseType',
            'Test\TestCliType',
            'Test\TestWebType',
        ];

        $this->assertEquals($expected, $classes);
    }


    public function testDocDescription()
    {
        $reflect = new \ReflectionClass(RandoDoc::class);
        $doc = $reflect->getDocComment();

        $expected = "Test this stuff.\n\nI can still have **markdown** if I want.";
        $actual = Reflect::getDocDescription($doc ?: '');

        $this->assertEquals($expected, $actual);
    }


    public function testDocTags()
    {
        $reflect = new \ReflectionClass(DocTags::class);
        $doc = $reflect->getDocComment();

        $actual = Reflect::getDocTags($doc);

        $this->assertNull($actual['missing'] ?? null);

        $expected = [''];
        $this->assertEquals($expected, $actual['one']);

        $expected = ['', '"arg1"'];
        $this->assertEquals($expected, $actual['two']);

        $expected = ['1 2 3 4'];
        $this->assertEquals($expected, $actual['three']);
    }


    public function testDocTagsFilter()
    {
        $reflect = new \ReflectionClass(DocTags::class);
        $doc = $reflect->getDocComment();

        $actual = Reflect::getDocTags($doc, ['missing', 'two']);

        $expected = [];
        $this->assertEquals($expected, $actual['missing']);

        $expected = ['', '"arg1"'];
        $this->assertEquals($expected, $actual['two']);
    }


    public function testDocTagsInline()
    {
        $reflect = new \ReflectionClass(DocTags::class);
        $doc = $reflect->getDocComment();

        $actual = Reflect::getDocTag($doc, 'safe');
        $expected = ['trailing slash * /'];
        $this->assertEquals($expected, $actual);

        $reflect = $reflect->getReflectionConstant('OK');
        $doc = $reflect->getDocComment();

        $actual = Reflect::getDocTag($doc, 'inline');
        $expected = ['ok? /'];
        $this->assertEquals($expected, $actual);
    }
}


/**
 * Test this stuff.
 *
 * I can still have **markdown** if I want.
 *
 * @package blah/blah/blah
 */
class RandoDoc {}



/**
 * Don't pick up @inline tags.
 *
 * @one
 * @two
 * @two "arg1"
 * @three 1 2 3 4
 * @safe trailing slash * /
 */
class DocTags {
    /** @inline ok? / */
    const OK = 1;
}
