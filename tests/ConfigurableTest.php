<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Configure;
use karmabunny\kb\Configurable;
use karmabunny\kb\ConfigurableInit;
use karmabunny\kb\UpdateTrait;
use PHPUnit\Framework\TestCase;


/**
 * Test the Arrays helpers.
 */
final class ConfigurableTest extends TestCase
{
    public function testAnonConfig()
    {
        // Test object passthrough.
        $object = new AnonObject1();
        $actual = Configure::configure($object);

        $this->assertSame($object, $actual);

        // Test class name.
        $actual = Configure::configure(AnonObject1::class);

        $this->assertInstanceOf(AnonObject1::class, $actual);
        $this->assertNull($actual->ok);

        // Test config array.
        $actual = Configure::configure([ AnonObject1::class => [
            'ok' => 'yes',
        ]]);

        $this->assertInstanceOf(AnonObject1::class, $actual);
        $this->assertEquals('yes', $actual->ok);
    }


    public function testConfigurable()
    {
        // test object passthrough.
        $object = new ConfigObject();
        $object->prop3 = 'hello';
        $actual = Configure::configure($object);

        $this->assertInstanceOf(ConfigObject::class, $actual);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertEquals('hello', $actual->prop3);
        $this->assertEquals('world', $actual->prop4);

        // test class name.
        $actual = Configure::configure(ConfigObject::class);

        $this->assertInstanceOf(ConfigObject::class, $actual);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertNull($actual->prop3);
        $this->assertEquals('world', $actual->prop4);

        // test config array.
        $actual = Configure::configure([ ConfigObject::class => [
            'prop3' => 'hello',
            'prop4' => 'sunshine',
        ]]);

        $this->assertInstanceOf(ConfigObject::class, $actual);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertEquals('hello', $actual->prop3);
        $this->assertEquals('sunshine', $actual->prop4);
    }


    public function testAssertions()
    {
        // Test assert: object.
        $object = new AnonObject2();
        $actual = Configure::configure($object, BaseObject::class);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertInstanceOf(AnonObject2::class, $actual);

        // Test assert: class name.
        $actual = Configure::configure(AnonObject2::class, BaseObject::class);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertInstanceOf(AnonObject2::class, $actual);

        // Test assert: config.
        $actual = Configure::configure([ AnonObject2::class => [] ], BaseObject::class);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertInstanceOf(AnonObject2::class, $actual);

        // Quick check that init() didn't fire off.
        $this->assertNull($actual->prop1);
        $this->assertNull($actual->prop2);

        // Test assert failure.
        try {
            $actual = Configure::configure(AnonObject1::class, BaseObject::class);
            $this->fail("Expected exception");
        }
        catch (Throwable $error) {
            $this->assertInstanceOf(InvalidArgumentException::class, $error);
        }
    }


    public function testInit()
    {
        // test object passthrough.
        $object = new ConfigObjectInit();
        $actual = Configure::configure($object);

        $this->assertInstanceOf(ConfigObjectInit::class, $actual);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertEquals('init', $actual->prop5);

        // test class name.
        $actual = Configure::configure(ConfigObjectInit::class);

        $this->assertInstanceOf(ConfigObjectInit::class, $actual);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertEquals('init', $actual->prop5);

        // test config array.
        $actual = Configure::configure([ ConfigObjectInit::class => [] ]);

        $this->assertInstanceOf(ConfigObjectInit::class, $actual);
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertEquals('init', $actual->prop5);
    }


    public function testConfigBulk()
    {
        $configs = [
            new AnonObject1(),
            [AnonObject2::class => [
                'prop2' => 'abc',
            ]],
            [ConfigObject::class => [
                'prop3' => 'hello',
            ]],
            ConfigObjectInit::class,
        ];

        // No assert.
        $actual = Configure::all($configs);
        $this->assertCount(4, $actual);

        // Test assertion.
        try {
            Configure::all($configs, BaseObject::class);
            $this->fail('Expected exception');
        }
        catch (Throwable $error) {
            $this->assertInstanceOf(InvalidArgumentException::class, $error);
        }

        // Try again.
        array_shift($configs);
        $actual = Configure::all($configs, BaseObject::class);

        $this->assertInstanceOf(BaseObject::class, $actual[0]);
        $this->assertInstanceOf(AnonObject2::class, $actual[0]);
        $this->assertNull($actual[0]->prop1);
        $this->assertEquals('abc', $actual[0]->prop2);

        $this->assertInstanceOf(BaseObject::class, $actual[1]);
        $this->assertInstanceOf(ConfigObject::class, $actual[1]);
        $this->assertEquals('hello', $actual[1]->prop3);
        $this->assertEquals('world', $actual[1]->prop4);

        $this->assertInstanceOf(BaseObject::class, $actual[2]);
        $this->assertInstanceOf(ConfigObjectInit::class, $actual[2]);
        // Not default.
        $this->assertEquals('init', $actual[2]->prop5);

        // Test /w no init.
        $actual = Configure::all($configs, BaseObject::class, false);
        $this->assertNull($actual[0]->prop1);
        $this->assertEquals('default', $actual[2]->prop5);

        // Test manual init.
        $actual = Configure::all($configs, BaseObject::class, false);
        Configure::initAll($actual);
        $this->assertNull($actual[0]->prop1);
        $this->assertEquals('init', $actual[2]->prop5);

        // Test forceful init.
        $actual = Configure::all($configs, BaseObject::class, false);
        Configure::initAll($actual, true);
        $this->assertEquals('init', $actual[0]->prop1);
        $this->assertEquals('init', $actual[2]->prop5);
    }

}


interface BaseObject {}


class AnonObject1
{
    public $ok;
}


class AnonObject2 implements BaseObject
{
    public $prop1;

    public $prop2;

    public function init()
    {
        $this->prop1 = 'init';
    }
}


class ConfigObject implements BaseObject, Configurable
{
    use UpdateTrait;

    public $prop3;

    public $prop4 = 'world';
}


class ConfigObjectInit implements BaseObject, ConfigurableInit
{
    use UpdateTrait;

    public $prop5 = 'default';

    public function init()
    {
        $this->prop5 = 'init';
    }
}
