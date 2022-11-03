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
        $this->assertCount(1, $check['hookOne'] ?? []);
        $this->assertCount(0, $check['hookTwo'] ?? []);
        $this->assertCount(0, $check['hookThree'] ?? []);

        // Parent hook record exists.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['a', 'b']];
        $actual = $check['hookOne'];
        $this->assertEquals($expected, $actual);


        $object->anotherMethod();

        // First hook has been called twice (myMethod and anotherMethod).
        $this->assertCount(2, $check['hookOne'] ?? []);
        $this->assertCount(0, $check['hookTwo'] ?? []);
        $this->assertCount(1, $check['hookThree'] ?? []);


        // Hook has two calls, one for each method.
        $expected = [];
        $expected[] = [ParentClass::class, ParentClass::class, ['a', 'b']];
        $expected[] = [ParentClass::class, ParentClass::class, ['test1', 'test2']];
        $actual = $check['hookOne'];
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
        $this->assertCount(1, $check['hookOne'] ?? []);
        $this->assertCount(1, $check['hookTwo'] ?? []);
        $this->assertCount(0, $check['hookThree'] ?? []);

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
        $this->assertCount(2, $check['hookOne'] ?? []);
        $this->assertCount(1, $check['hookTwo'] ?? []);

        // TODO this isn't right.
        $this->assertCount(0, $check['hookThree'] ?? []);

        // The hook receives one of each class.
        $expected = [];
        $expected[] = [ParentClass::class, ChildClass::class, ['a', 'b']];
        $expected[] = [ParentClass::class, ChildClass::class, ['child1', 'child2']];
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
        $this->_hook(__FUNCTION__, $arg1, $arg2);
    }


    public function anotherMethod()
    {
        $this->_hook(__FUNCTION__, 'test1', 'test2');
    }


    /**
     *
     * @hook myMethod
     * @hook anotherMethod
     */
    protected function hookOne($arg1, $arg2)
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, [$arg1, $arg2]];
    }


    #[Hook('myMethod')]
    protected function hookAttribute()
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, []];
    }


    /**
     *
     * @hook anotherMethod
     * @return void
     */
    private static function hookThree()
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, []];
    }
}


class ChildClass extends ParentClass
{
    /**
     * @hook myMethod
     */
    public function hookTwo($arg1, $arg2)
    {
        static::$check[__FUNCTION__][] = [self::class, static::class, [$arg1, $arg2]];
    }


    public function anotherMethod()
    {
        $this->_hook(__FUNCTION__, 'child1', 'child2');
    }
}
