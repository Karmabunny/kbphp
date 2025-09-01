<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\interfaces\ValidatesInterface;
use karmabunny\kb\Collection;
use karmabunny\kb\DocValidatorTrait;
use karmabunny\kb\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Test the Doc Validator.
 */
final class DocValidatorTest extends TestCase {

    private static function createThing()
    {
        return new DocThing([
            'id' => '111',
            'decimal' => 4.44,
            'integer' => 5,
            'generic' => '777',
            'list' => [],
            'object' => new DumbThing(),
        ]);
    }

    public function testGood()
    {
        $thing = self::createThing();
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
                'decimal',
                'integer',
                'generic',
                'list',
                'object',
            ];

            $this->assertEquals($expected, array_keys($exception->errors));
            foreach ($expected as $name) {
                $this->assertEquals("Property is required.", $exception->errors[$name]['required']);
            }
        }
    }


    public function testInteger()
    {
        try {
            $thing = self::createThing();
            $thing->id = 123.123;
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['id'], array_keys($exception->errors));

            $expected = 'Expected int instead of float.';
            $this->assertEquals($expected, $exception->errors['id'][0]);
        }
    }


    public function testFloat()
    {
        $thing = self::createThing();

        // integers are okay.
        $thing->optional = 1;
        $thing->validate();

        // strings are okay.
        $thing->decimal = '0.1';
        $thing->validate();

        // Empty strings are not ok.
        try {
            $thing->decimal = '';
            $thing->validate();
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['decimal'], array_keys($exception->errors));
        }

        // Rando strings are not ok.
        try {
            $thing->decimal = 'asdf';
            $thing->validate();
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['decimal'], array_keys($exception->errors));
        }
    }


    public function testObject()
    {
        try {
            $thing = self::createThing();
            $thing->object = (object)[];
            $thing->local = (object)[];
            $thing->validate();
        }
        catch (ValidationException $exception) {
            // echo print_r($exception->errors, true), PHP_EOL;
            $this->assertEquals(['object', 'local'], array_keys($exception->errors));

            $expected = "Expected \karmabunny\\kb\\Collection instead of \stdClass.";
            $this->assertEquals($expected, $exception->errors['object'][0]);

            $expected = "Expected \karmabunny\\kb\\Collection|null instead of \stdClass.";
            $this->assertEquals($expected, $exception->errors['local'][0]);
        }
    }


    public function testList()
    {
        $thing = self::createThing();

        // integer list - ok
        $thing->list = [1,2,3];
        $thing->validate();

        // integer strings - ok
        $thing->list = ['1', '2', '123'];
        $thing->validate();

        // Wrong type - string
        try {
            $thing->list = 'string thing';
            $thing->validate();
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['list'], array_keys($exception->errors));

            $expected = 'Expected int[] instead of string.';
            $actual = $exception->errors['list'][0];
            $this->assertEquals($expected, $actual);
        }

        // Wrong item type - string[]
        try {
            $thing->list = ['one', 'two', 'three'];
            $thing->validate();
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['list'], array_keys($exception->errors));

            $expected = 'Expected int[] instead of string[].';
            $actual = $exception->errors['list'][0];
            $this->assertEquals($expected, $actual);
        }
    }
}

class DumbThing extends Collection {}


class DocThing extends Collection implements ValidatesInterface {
    use DocValidatorTrait;

    /** @var int required */
    public $id;

    /** @var float|null optional */
    public $optional;

    /** @var int */
    public $default = 333;

    /** @var float */
    public $decimal;

    /** @var int */
    public $integer;

    /** @var array|string */
    public $generic;

    /** @var int[] */
    public $list;

    /** @var karmabunny\kb\Collection */
    public $object;

    /** @var Collection|null optional */
    public $local;

    public static function namespaces(): array
    {
        return ['karmabunny\\kb\\'];
    }
}
