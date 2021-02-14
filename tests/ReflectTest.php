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
}
