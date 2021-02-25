<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\RulesValidatorTrait;
use karmabunny\kb\Validates;
use karmabunny\kb\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Test the Rules Validator.
 *
 * @todo Test custom 'validity' helper.
 * @todo Test filter rules.
 * @todo Adjust field errors to include the error type as the key.
 * @todo More validity things
 *   - class instanceof
 *   - is float
 *   - multi-validations - ..how? Reflect on first parameter?
 */
final class RulesValidatorTest extends TestCase {

    public function testDefaultScenario()
    {
        // Default required only needs 'id'.
        $thing = new FieldThing([]);

        try {
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertEquals(['id'], array_keys($exception->errors));
            $this->assertTrue(isset($exception->errors['id']['required']));
        }

        // Valid case.
        $thing->id = 123;
        $thing->validate();
    }


    public function testFieldFailures()
    {
        $thing = new FieldThing([
            // positive int
            'id' => -1,

            // numeric
            'amount' => 'uhh',

            // 10-20, positive int
            'ten_twenty' => -100.5,

            // email
            'address' => 'not-an-email',
        ]);

        try {
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = ['id', 'ten_twenty', 'amount', 'address'];

            $this->assertEquals($expected, array_keys($exception->errors));
            $this->assertEquals(1, count($exception->errors['id']));
            $this->assertEquals(1, count($exception->errors['amount']));
            // Two errors here: positiveInt + range.
            $this->assertEquals(2, count($exception->errors['ten_twenty']));
            $this->assertEquals(1, count($exception->errors['address']));
        }

        // Valid case.
        $thing->id = 123;
        $thing->amount = "12.34"; // numeric strings are ok.
        $thing->ten_twenty = 15;
        $thing->address = 'a@b.com';
        $thing->validate();
    }


    /**
     * Testing the 'required' scenario.
     */
    public function testAllRequired()
    {
        $thing = new FieldThing([
            'id' => 123,
            // Missing fields
            // - amount
            // - ten_twenty
            // - address
        ]);

        try {
            $thing->validate(FieldThing::SCENARIO_ALL_REQUIRED);

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            // 'default' shouldn't be in here appear.
            $expected = ['amount', 'ten_twenty', 'address'];
            $this->assertEquals($expected, array_keys($exception->errors));
            $this->assertTrue(isset($exception->errors['amount']['required']));
            $this->assertTrue(isset($exception->errors['ten_twenty']['required']));
            $this->assertTrue(isset($exception->errors['address']['required']));
        }

        // Valid case.
        $thing->id = 123;
        $thing->amount = 12.34;
        $thing->ten_twenty = 15;
        $thing->address = 'a@b.com';
        $thing->validate();
    }


    /**
     * Testing inline validators in the 'custom' scenario.
     */
    public function testCustomInline()
    {
        $thing = new FieldThing([
            'id' => 100,

            // even, positive, 10-20
            'ten_twenty' => -13,

            // email + end in karmabunny.com.au
            'address' => 'a@b.com',
        ]);

        try {
            $thing->validate(FieldThing::SCENARIO_CUSTOM);

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertTrue(empty($exception->errors['required']));

            // MORE errors. yay.
            $this->assertEquals(3, count($exception->errors['ten_twenty']));
            $this->assertEquals(1, count($exception->errors['address']));
        }

        // Valid case.
        $thing->ten_twenty = 14;
        $thing->address = 'test@karmabunny.com.au';
        $thing->validate();
    }


    /**
     * Testing modifying values.
     */
    // public function testFilterRules()
    // {

    // }
}


class FieldThing extends Collection implements Validates
{
    use RulesValidatorTrait;

    const SCENARIO_UPDATE = 'update';
    const SCENARIO_ALL_REQUIRED = 'submit';
    const SCENARIO_CUSTOM = 'custom';

    /** @var int*/
    public $id;

    /** @var float|null */
    public $amount;

    /** @var int */
    public $default = 333;

    /** @var int */
    public $ten_twenty;

    /** @var string */
    public $address;


    public function rules(string $scenario = null): array
    {
        $rules = [
            'required' => ['id'],
            'positiveInt' => ['id', 'ten_twenty'],
            'numeric' => ['amount'],
            'range' => ['ten_twenty', 'args' => [10, 20]],
            'email' => ['address'],
        ];

        // Different scenario.
        if ($scenario === self::SCENARIO_ALL_REQUIRED) {
            $rules['required'] = ['id', 'amount', 'default', 'ten_twenty', 'address'];
        }

        // Inline rules!
        else if ($scenario === self::SCENARIO_CUSTOM) {
            $rules['even'] = ['ten_twenty', 'func' => function($value) {
                if ($value % 2) throw new ValidationException('Not even.');
            }];

            // Non-keyed rules are a thing, but not recommended.
            $rules[] = ['address', 'func' => [self::class, 'matchDomain']];
        }

        return $rules;
    }


    public static function matchDomain($value)
    {
        if (!preg_match('/@karmabunny\.com\.au$/', $value)) {
            throw new ValidationException('Domain must be karmabunny.com.au.');
        }
    }
}
