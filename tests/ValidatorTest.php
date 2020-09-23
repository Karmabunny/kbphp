<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\ValidatorTrait;
use karmabunny\kb\Validates;
use karmabunny\kb\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Test the Validator helper utilities.
 */
final class ValidatorTest extends TestCase {

    public static function thingo()
    {
        return new Thing([
            'id' => 111,
            'amount' => 222.22,
            'scalar' => 4.44,
            'nope' => 5,
            'okay' => '666',
            'another' => 777,
            'class' => new Collection([]),
        ]);
    }


    public function testGood()
    {
        $thing = self::thingo();
        $thing->validate();
        $this->assertTrue(true);
    }


    public function testRequired()
    {
        try {
            $thing = new Thing([]);
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = [
                'id',
                'scalar',
                'nope',
                'okay',
                'another',
            ];
            $this->assertEquals($expected, $exception->properties);
            $this->assertEquals($expected, $exception->required);
            $this->assertEquals($expected, array_keys($exception->errors));

            foreach ($expected as $name) {
                $this->assertEquals("Property '{$name}' is required.", $exception->errors[$name]);
            }
        }
    }


    public function testInteger()
    {
        try {
            $thing = self::thingo();
            $thing->id = 123.123;
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['id'], $exception->properties);
            $this->assertEquals([], $exception->required);

            $expected = "Property value 'id' is float instead of int.";
            $this->assertEquals($expected, $exception->errors['id']);
            $this->assertEquals($expected, $exception->getMessage());
        }
    }


    public function testFloat()
    {
        $thing = self::thingo();
        $thing->amount = 1;
        $thing->scalar = 0;
        $thing->validate();
        $this->assertTrue(true);
    }


    public function testObject()
    {
        try {
            $thing = self::thingo();
            $thing->object = new \stdClass();
            $thing->validate();
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['object'], $exception->properties);
            $this->assertEquals([], $exception->required);

            $expected = "Property value 'object' is object instead of \\karmabunny\\kb\\Collection|null.";
            $this->assertEquals($expected, $exception->errors['object']);
            $this->assertEquals($expected, $exception->getMessage());
        }
    }
}


class Thing extends Collection implements Validates {
    use ValidatorTrait;

    /** @var int required */
    public $id;

    /** @var float|null optional */
    public $amount;

    /** @var int */
    public $default = 333;

    /** @var float */
    public $scalar;

    /** @var int */
    public $nope;

    /** @var string|int */
    public $okay;

    /** @var string|int */
    public $another;

    /** @var \karmabunny\kb\Collection|null */
    public $object;
}
