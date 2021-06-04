<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the compat aliases.
 */
final class AliasTest extends TestCase
{

    public function testAliases()
    {
        $this->assertInterfaceExists(\karmabunny\kb\Copyable::class);
        $this->assertInterfaceExists(\karmabunny\kb\Arrayable::class);
        $this->assertInterfaceExists(\karmabunny\kb\Loggable::class);
        $this->assertInterfaceExists(\karmabunny\kb\Validates::class);
        $this->assertInterfaceExists(\karmabunny\kb\Validator::class);
        $this->assertClassExists(\karmabunny\kb\XML::class);
        $this->assertClassExists(\karmabunny\kb\ValidationException::class);
        $this->assertClassExists(\karmabunny\kb\UrlDecodeException::class);
        $this->assertClassExists(\karmabunny\kb\XMLAssertException::class);
        $this->assertClassExists(\karmabunny\kb\XMLException::class);
        $this->assertClassExists(\karmabunny\kb\XMLParseException::class);
        $this->assertClassExists(\karmabunny\kb\XMLSchemaException::class);

        // Alias checks out.
        $error = new \karmabunny\kb\XMLException('what a mess');
        $this->assertInstanceOf(\karmabunny\kb\Errors\DocException::class, $error);
        $this->assertEquals(get_class($error), \karmabunny\kb\Errors\DocException::class);

        // Inheritance still works.
        $error = new \karmabunny\kb\XMLAssertException('what a mess');
        $this->assertInstanceOf(\karmabunny\kb\XMLException::class, $error);
        $this->assertInstanceOf(\karmabunny\kb\Errors\DocException::class, $error);
    }


    public function assertClassExists(string $class)
    {
        $this->assertTrue(class_exists($class, true), "Class {$class} does not exist");
    }


    public function assertInterfaceExists(string $interface)
    {
        $this->assertTrue(interface_exists($interface, true), "Interface {$interface} does not exist");
    }
}
