<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\DocValidatorTrait;
use karmabunny\kb\Validates;
use karmabunny\kb\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Test the Validator helper utilities.
 */
final class DocValidatorTest extends TestCase {

    public static function thingo()
    {
        return new DocThing([
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
            $thing = new DocThing([]);
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

            $this->assertEquals($expected, array_keys($exception->errors));
            foreach ($expected as $name) {
                $this->assertEquals("Property is required.", $exception->errors[$name]['required']);
            }

            // $message = 'Validation failed for ' . implode(', ', $expected);
            // $this->assertEquals($message, $exception->getMessage());
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
            $this->assertEquals(['id'], array_keys($exception->errors));

            $expected = "Property is float instead of int.";
            $this->assertEquals($expected, $exception->errors['id'][0]);
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
            $thing->local = new \stdClass();
            $thing->validate();
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['object', 'local'], array_keys($exception->errors));

            $expected = "Property is object instead of \\karmabunny\\kb\\Collection|null.";
            $this->assertEquals($expected, $exception->errors['object'][0]);

            $expected = "Property is object instead of Collection|null.";
            $this->assertEquals($expected, $exception->errors['local'][0]);
        }
    }
}


class DocThing extends Collection implements Validates {
    use DocValidatorTrait;

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

    /** @var Collection|null */
    public $local;

    public static function namespaces(): array
    {
        return ['\\karmabunny\\kb\\'];
    }
}
