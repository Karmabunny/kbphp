<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\RulesClassValidator;
use karmabunny\kb\RulesValidatorInterface;
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
final class RulesValidatorTest extends TestCase
{

    public function dataFields()
    {
        return [
            'old' => [ OldFieldThing::class ],
            'new' => [ NewFieldThing::class ],
        ];
    }


    /**
     * @param class-string<BaseFieldThing> $class
     * @dataProvider dataFields
     */
    public function testDefaultScenario($class)
    {
        // Default required only needs 'id'.
        $thing = new $class([]);

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


    /**
     * @param class-string<BaseFieldThing> $class
     * @dataProvider dataFields
     */
    public function testFieldFailures($class)
    {
        $thing = new $class([
            // positive int
            'id' => -1,

            // numeric
            'amount' => 'uhh',

            // 10-20, positive int
            'ten_twenty' => -100.5,

            // email
            'address' => 'not-an-email',

            'thirty_forty' => 50,
            'length_one' => 'asdf asdf asdf',
            'length_two' => 'a',
        ]);

        try {
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = ['id', 'ten_twenty', 'amount', 'address', 'thirty_forty', 'length_one', 'length_two'];
            $actual = array_keys($exception->errors);

            sort($expected);
            sort($actual);

            $this->assertEquals($expected, $actual);
            $this->assertEquals(1, count($exception->errors['id']));
            $this->assertEquals(1, count($exception->errors['amount']));

            // Two errors here: positiveInt + range.
            $this->assertEquals(2, count($exception->errors['ten_twenty']));

            // And a bunch more.
            $this->assertEquals(1, count($exception->errors['address']));
            $this->assertEquals(1, count($exception->errors['thirty_forty']));
            $this->assertEquals(1, count($exception->errors['length_one']));
            $this->assertEquals(1, count($exception->errors['length_two']));
        }

        // Valid case.
        $thing->id = 123;
        $thing->amount = "12.34"; // numeric strings are ok.
        $thing->ten_twenty = 15;
        $thing->address = 'a@b.com';
        $thing->thirty_forty = 35;
        $thing->length_one = 'asdfasdf';
        $thing->length_two = 'asdfasdf';
        $thing->validate();
    }


    /**
     * Testing the 'required' scenario.
     *
     * @param class-string<BaseFieldThing> $class
     * @dataProvider dataFields
     */
    public function testAllRequired($class)
    {
        $thing = new $class([
            'id' => 123,
            // Missing fields
            // - amount
            // - ten_twenty
            // - address
        ]);

        try {
            $thing->validate(BaseFieldThing::SCENARIO_ALL_REQUIRED);

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
     *
     * @param class-string<BaseFieldThing> $class
     * @dataProvider dataFields
     */
    public function testCustomInline($class)
    {
        $thing = new $class([
            'id' => 100,

            // even, positive, 10-20
            'ten_twenty' => -13,

            // range - 20-30
            'twenty_thirty' => 16,

            // email + end in karmabunny.com.au
            'address' => 'a@b.com',
        ]);

        try {
            $thing->validate(BaseFieldThing::SCENARIO_CUSTOM);

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertTrue(empty($exception->errors['required']));

            // MORE errors. yay.
            $this->assertEquals(3, count($exception->errors['ten_twenty']), print_r($exception->errors, true));
            $this->assertEquals(1, count($exception->errors['address']));
            $this->assertEquals(1, count($exception->errors['twenty_thirty']));
        }

        // Valid case.
        $thing->ten_twenty = 14;
        $thing->twenty_thirty = 25;
        $thing->address = 'test@karmabunny.com.au';
        $thing->validate();
    }


    /**
     * Testing multi-check behaviour.
     *
     * @param class-string<BaseFieldThing> $class
     * @dataProvider dataFields
     */
    public function testMultiCheck($class)
    {
        $thing = new $class([
            'id' => 100,
            'ten_twenty' => 20,
            'twenty_thirty' => 20,
            'address' => 'a@b.com',
        ]);

        try {
            $thing->validate(BaseFieldThing::SCENARIO_MULTI);
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertTrue(empty($exception->errors['required']));

            $this->assertEquals(1, count($exception->errors['ten_twenty']));
            $this->assertEquals(1, count($exception->errors['twenty_thirty']));
        }

        // Valid case.
        $thing->twenty_thirty = 21;
        $thing->validate();
    }


    /**
     * Testing modifying values.
     */
    // public function testFilterRules()
    // {

    // }
}


abstract class BaseFieldThing extends Collection implements Validates
{
    use RulesValidatorTrait;

    const SCENARIO_UPDATE = 'update';
    const SCENARIO_ALL_REQUIRED = 'submit';
    const SCENARIO_CUSTOM = 'custom';
    const SCENARIO_MULTI = 'multi';

    /** @var int*/
    public $id;

    /** @var float|null */
    public $amount;

    /** @var int */
    public $default = 333;

    /** @var int */
    public $ten_twenty;

    /** @var int */
    public $twenty_thirty;

    /** @var string */
    public $address;

    /** @var int */
    public $thirty_forty = 35;

    /** @var string */
    public $length_one = 'asdfasdf';

    /** @var string */
    public $length_two = 'asdfasdf';


    public static function matchDomain($value)
    {
        if (!preg_match('/@karmabunny\.com\.au$/', $value)) {
            throw new ValidationException('Domain must be karmabunny.com.au.');
        }
    }
}


class OldFieldThing extends BaseFieldThing
{

    /** @inheritdoc */
    public function rules(?string $scenario = null): array
    {
        $rules = [
            'required' => ['id'],
            'positiveInt' => ['id', 'ten_twenty'],
            'numeric' => ['amount'],
            'range' => [
                ['ten_twenty', 'args' => [10, 20]],
                ['thirty_forty', 'args' => [30, 40]],
            ],
            'email' => ['address'],
            'length' => [
                ['length_one', 'args' => [0, 10]],
                ['length_two', 'args' => [5, 20]],
            ],
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

            $rules[] = ['address', 'func' => [self::class, 'matchDomain']];

            // Per-field args.
            $rules['range'] = [
                ['ten_twenty', 'args' => [10, 20]],
                ['twenty_thirty', 'args' => [20, 30]],
            ];
        }
        else if ($scenario === self::SCENARIO_MULTI) {
            $rules['allUnique'] = ['ten_twenty', 'twenty_thirty', 'multi' => true];
        }

        return $rules;
    }
}


class NewFieldThing extends BaseFieldThing
{

    /** @inheritdoc */
    public function getValidator(): RulesValidatorInterface
    {
        return new RulesClassValidator($this);
    }


    /** @inheritdoc */
    public function rules(?string $scenario = null): array
    {
        $rules = [
            'required' => ['id'],
            ['positiveInt' => ['id', 'ten_twenty']],
            ['numeric' => ['amount']],
            ['range' => ['ten_twenty', 'between' => [10, 20]]],
            ['range' => ['thirty_forty', 'between' => [30, 40]]],
            ['email' => ['address']],
            'length' => [
                ['length_one', 'min' => 0, 'max' => 10],
                ['length_two', 'min' => 5, 'max' => 20],
            ],
        ];

        // Different scenario.
        if ($scenario === self::SCENARIO_ALL_REQUIRED) {
            $rules['required'] = ['id', 'amount', 'default', 'ten_twenty', 'address'];
        }

        // Inline rules!
        else if ($scenario === self::SCENARIO_CUSTOM) {
            // Keys are ignored.
            $rules['even'] = ['ten_twenty', 'func' => function($value) {
                if ($value % 2) throw new ValidationException('Not even.');
            }];

            // Non-keyed rules.
            $rules[] = ['address', 'func' => [self::class, 'matchDomain']];

            // Per-field args.
            $rules[]['range'] = [ 'twenty_thirty', 'between' => [20, 30]];
        }
        else if ($scenario === self::SCENARIO_MULTI) {
            $rules[]['allUnique'] = ['ten_twenty', 'twenty_thirty'];
        }

        return $rules;
    }

}
