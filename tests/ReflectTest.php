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
}

/**
 * Test this stuff.
 *
 * I can still have **markdown** if I want.
 *
 * @package blah/blah/blah
 */
class RandoDoc {}
