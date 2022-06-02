<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

// namespace Tests;

use karmabunny\kb\Hook;
use karmabunny\kb\HooksTrait;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class HooksTest extends TestCase
{

    public function testHooks()
    {
        ParentClass::$check = [];
        $check = &ParentClass::$check;
        $this->assertCount(0, $check);

        // The presence of a child class should pose no problems.
        $child = new ChildClass();
        $child->myMethod(1, 2);

        ParentClass::$check = [];
        $check = &ParentClass::$check;
        $this->assertCount(0, $check);

        // Begin the test.
        $object = new ParentClass();
        $object->myMethod('a', 'b');

        // Only hookOne has been called.
        $this->assertCount(1, $check['myMethod'] ?? []);
        $this->assertCount(0, $check['anotherMethod'] ?? []);
        $this->assertCount(0, $check['staticMethod'] ?? []);
        $this->assertCount(1, $check['hookOne'] ?? []);
        $this->assertCount(0, $check['hookTwo'] ?? []);
        $this->assertCount(0, $check['hookThree'] ?? []);

        // Parent method record exists.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['a', 'b']];
        $actual = $check['myMethod'];
        $this->assertEquals($expected, $actual);

        // Parent hook record exists.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['a', 'b']];
        $actual = $check['hookOne'];
        $this->assertEquals($expected, $actual);


        $object->anotherMethod();

        // First hook has been called twice (myMethod and anotherMethod).
        $this->assertCount(1, $check['myMethod'] ?? []);
        $this->assertCount(1, $check['anotherMethod'] ?? []);
        $this->assertCount(0, $check['staticMethod'] ?? []);
        $this->assertCount(2, $check['hookOne'] ?? []);
        $this->assertCount(0, $check['hookTwo'] ?? []);
        $this->assertCount(1, $check['hookThree'] ?? []);

        // Method exists.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, []];
        $actual = $check['anotherMethod'];
        $this->assertEquals($expected, $actual);

        // Hook has two calls, one for each method.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['a', 'b']];
        $expected[] = [ParentClass::class, ParentClass::class, []];
        $actual = $check['hookOne'];
        $this->assertEquals($expected, $actual);
    }


    public function testStaticHook()
    {
        ParentClass::$check = [];
        $check = &ParentClass::$check;
        $this->assertCount(0, $check);

        ParentClass::staticMethod('blah');

        // hookOne cannot be called, because it's non-static.
        $this->assertCount(0, $check['myMethod'] ?? []);
        $this->assertCount(0, $check['anotherMethod'] ?? []);
        $this->assertCount(1, $check['staticMethod'] ?? []);
        $this->assertCount(0, $check['hookOne'] ?? []);
        $this->assertCount(0, $check['hookTwo'] ?? []);
        $this->assertCount(1, $check['hookThree'] ?? []);

        // Method exists.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['blah']];
        $actual = $check['staticMethod'];
        $this->assertEquals($expected, $actual);

        // Hook has two calls, one for each method.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['blah']];
        $actual = $check['hookThree'];
        $this->assertEquals($expected, $actual);
    }


    public function testChildHooks()
    {
        ParentClass::$check = [];
        $check = &ParentClass::$check;
        $this->assertCount(0, $check);

        $child = new ChildClass();
        $child->myMethod('a', 'b');

        // Two hooks exist on the child.
        $this->assertCount(1, $check['myMethod'] ?? []);
        $this->assertCount(0, $check['anotherMethod'] ?? []);
        $this->assertCount(0, $check['staticMethod'] ?? []);
        $this->assertCount(1, $check['hookOne'] ?? []);
        $this->assertCount(1, $check['hookTwo'] ?? []);
        $this->assertCount(0, $check['hookThree'] ?? []);

        // method exists.
        $expected = [];
        $expected[] = [ParentClass::class, ChildClass::class, ['a', 'b']];
        $actual = $check['myMethod'];
        $this->assertEquals($expected, $actual);

        // Hook one.
        $expected = [];
        $expected[] = [ParentClass::class, ChildClass::class, ['a', 'b']];
        $actual = $check['hookOne'];
        $this->assertEquals($expected, $actual);

        // And hook two.
        $expected = [];
        $expected[] = [ChildClass::class, ChildClass::class, ['a', 'b']];
        $actual = $check['hookTwo'];
        $this->assertEquals($expected, $actual);


        $child->anotherMethod();

        // 'anotherMethod' is still called on the child.
        $this->assertCount(1, $check['myMethod'] ?? []);
        $this->assertCount(1, $check['anotherMethod'] ?? []);
        $this->assertCount(0, $check['staticMethod'] ?? []);
        $this->assertCount(2, $check['hookOne'] ?? []);
        $this->assertCount(1, $check['hookTwo'] ?? []);
        $this->assertCount(1, $check['hookThree'] ?? []);

        // But reports a different class.
        $expected = [];
        $expected[] = [ChildClass::class, ChildClass::class, []];
        $actual = $check['anotherMethod'];
        $this->assertEquals($expected, $actual);

        // Similarly, the hook receives one of each class.
        $expected = [];
        $expected[] = [ParentClass::class, ChildClass::class, ['a', 'b']];
        $expected[] = [ParentClass::class, ChildClass::class, []];
        $actual = $check['hookOne'];
        $this->assertEquals($expected, $actual);

    }
}


/**
 *
 */
class ParentClass
{
    use HooksTrait;

    /** @var array [ method => [ self, static, args ] ] */
    static $check = [];


    public function myMethod($arg1, $arg2)
    {
        self::_hook();
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }


    public function anotherMethod()
    {
        self::_hook();
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }


    public static function staticMethod($arg)
    {
        self::_hook();
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }


    /**
     *
     * @hook myMethod
     * @hook anotherMethod
     * @hook staticMethod
     */
    protected function hookOne()
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }


    #[Hook('myMethod')]
    protected function hookAttribute()
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }


    /**
     *
     * @hook anotherMethod
     * @hook staticMethod
     * @return void
     */
    private static function hookThree()
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }
}


class ChildClass extends ParentClass
{
    /**
     * @hook myMethod
     */
    public function hookTwo($arg1, $arg2)
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }


    public function anotherMethod()
    {
        self::_hook();
        static::$check[__FUNCTION__][] = [self::class, static::class, func_get_args()];
    }
}
