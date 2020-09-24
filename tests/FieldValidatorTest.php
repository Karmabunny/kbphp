<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\FieldValidatorTrait;
use karmabunny\kb\Validates;
use karmabunny\kb\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Test the Validator helper utilities.
 */
final class FieldValidatorTest extends TestCase {

    public function testRequired()
    {
        $thing = new FieldThing([]);

        try {
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = ['id', 'nope', 'okay'];

            $this->assertEquals($expected, array_keys($exception->errors));
            $this->assertTrue(isset($exception->errors['id']['required']));
            $this->assertFalse(isset($exception->errors['amount']['required']));
            $this->assertFalse(isset($exception->errors['default']['required']));
            $this->assertFalse(isset($exception->errors['scalar']['required']));
            $this->assertTrue(isset($exception->errors['nope']['required']));
            $this->assertTrue(isset($exception->errors['okay']['required']));
        }
    }


    public function testFailure()
    {
        $thing = new FieldThing([
            'id' => -1,
            'amount' => 'uhh',
            'scalar' => 123.123,
            'nope' => -100.5,
            'okay' => 'blah',
        ]);

        try {
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = ['id', 'nope', 'okay'];

            $this->assertEquals($expected, array_keys($exception->errors));
            $this->assertEquals(1, count($exception->errors['id']));
            $this->assertEquals(2, count($exception->errors['nope']));
            $this->assertEquals(1, count($exception->errors['okay']));
        }
    }

    public function testValid()
    {
        $thing = new FieldThing([
            'id' => 123,
            'nope' => 15,
            'okay' => 'a@b.com',
        ]);
        $thing->validate();
        $this->assertTrue(true);
    }
}


class FieldThing extends Collection implements Validates {
    use FieldValidatorTrait;

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

    public function fields(): array
    {
        return [
            'required' => [ 'id', 'default', 'nope', 'okay' ],
            'positiveInt' => [ 'id', 'nope' ],
            ['nope', 'range', 10, 20],
            ['okay', 'email'],
        ];
    }
}
