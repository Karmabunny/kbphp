<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\AttributeValidatorTrait;
use karmabunny\kb\Collection;
use karmabunny\kb\Rule;
use karmabunny\kb\Scenario;
use karmabunny\kb\Validates;
use karmabunny\kb\ValidationException;
use PHPUnit\Framework\TestCase;


/**
 * Test the attribute rules validator.
 */
class AttributeValidatorTest extends TestCase
{
    public function testEmpty()
    {
        try {
            $thing = new AttrThing([]);
            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = [
                'id',
                'amount',
            ];

            if (PHP_VERSION_ID >= 80000) {
                $expected[] = 'php8';
                $expected[] = 'php8_scenario';
            }

            $actual = array_keys($exception->errors);
            $this->assertEquals($expected, $actual);

            $this->assertTrue(isset($exception->errors['id']['required']));
            $this->assertTrue(isset($exception->errors['amount']['required']));
        }
    }


    public function testValid()
    {
        try {
            $thing = new AttrThing([
                'id' => -1234,

                // positiveInt
                'amount' => 400,

                // range - 10-20
                'ten_twenty' => 13,

                // email + end in karmabunny.com.au
                'address' => 'aaaa@karmabunny.com.au',

                // Not tested in PHP7
                'php8' => 'test@example.com',

                // Not tested in PHP7
                'php8_scenario' => 'OK',
            ]);

            $thing->validate();
            $this->assertTrue(true);
        }
        catch (ValidationException $exception) {
            $this->fail($exception->getMessage());
        }
    }


    public function testScenarios()
    {
        // Test required.
        // missing twenty_thirty + (php8_scenario, when applicable)
        try {
            $thing = new AttrThing([
                'id' => 100,
                'amount' => 400,
                'address' => 'aaaa@karmabunny.com.au',
                'php8' => 'test@example.com',
            ]);

            $thing->validate(AttrThing::SCENARIO_TEST);
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = [
                'twenty_thirty',
            ];

            if (PHP_VERSION_ID >= 80000) {
                $expected[] = 'php8_scenario';
            }

            $actual = array_keys($exception->errors);
            $this->assertEquals($expected, $actual);
        }

        // Just bad validations.
        try {
            $thing = new AttrThing([
                'ten_twenty' => -13,
                'twenty_thirty' => 10,
                'php8_scenario' => 'OK',
            ]);

            $thing->validate(AttrThing::SCENARIO_TEST);
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = [
                'ten_twenty',
                'twenty_thirty',
            ];

            $actual = array_keys($exception->errors);
            $this->assertEquals($expected, $actual);
        }

        try {
            $thing = new AttrThing([
                'ten_twenty' => 15,

                'twenty_thirty' => 25,

                // Not tested in PHP7
                'php8_scenario' => 'OK',
            ]);

            $thing->validate(AttrThing::SCENARIO_TEST);
            $this->assertTrue(true);
        }
        catch (ValidationException $exception) {
            $this->fail($exception->getMessage());
        }
    }


    public function testFailure()
    {
        try {
            $thing = new AttrThing([
                'id' => false,

                // positiveInt
                'amount' => -400,

                // range - 10-20
                'ten_twenty' => -13,

                // email, matchDomain
                'address' => 'eeeeee',
            ]);

            $thing->validate();

            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $expected = [
                'id',
                'amount',
                'ten_twenty',
                'address',
            ];

            if (PHP_VERSION_ID >= 80000) {
                $expected[] = 'php8';
                $expected[] = 'php8_scenario';
            }

            $actual = array_keys($exception->errors);
            $this->assertEquals($expected, $actual);

            $this->assertTrue(isset($exception->errors['id']['required']));
            $this->assertCount(1, $exception->errors['amount'] ?? []);
            $this->assertCount(1, $exception->errors['ten_twenty'] ?? []);
            $this->assertCount(2, $exception->errors['address'] ?? []);
        }
    }


    /**
     *
     * @requires PHP >= 8.0
     */
    public function testAttributes()
    {
        $thing = new AttrThing([
            'id' => 100,
            'amount' => 400,
            'ten_twenty' => 13,
            'twenty_thirty' => 26,
            'address' => 'aaaa@karmabunny.com.au',
        ]);

        // test valid.
        try {
            $thing->php8 = 'test@example.com';
            $thing->php8_scenario = 'OK';

            $thing->validate();
            $this->assertTrue(true);
        }
        catch (ValidationException $exception) {
            $this->fail($exception->getMessage());
        }

        // test required.
        try {
            $thing->php8 = null;
            $thing->php8_scenario = 'OK';

            $thing->validate();
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertTrue(isset($exception->errors['php8']['required']));
            $this->assertFalse(isset($exception->errors['php8_scenario']['required']));
        }

        // test invalid.
        try {
            $thing->php8 = 'eee';
            $thing->php8_scenario = 'OK';

            $thing->validate();
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertCount(3, $exception->errors['php8'] ?? []);
        }

        // test bad scenario.
        try {
            $thing->php8 = null;
            $thing->php8_scenario = null;

            $thing->validate(AttrThing::SCENARIO_TEST);
            $this->fail('Expected ValidationException.');
        }
        catch (ValidationException $exception) {
            $this->assertTrue(isset($exception->errors['php8_scenario']['required']));
        }

        // test good scenario.
        try {
            $thing->php8 = null;
            $thing->php8_scenario = 'OK';

            $thing->validate(AttrThing::SCENARIO_TEST);
            $this->assertTrue(true);
        }
        catch (ValidationException $exception) {
            $this->fail($exception->getMessage());
        }
    }
}


class AttrThing extends Collection implements Validates
{
    use AttributeValidatorTrait;

    const SCENARIO_TEST = 'TEST';

    /**
     * @var int
     * @rule required
     */
    public $id;

    /**
     * @var float|null
     * @rule positiveInt
     * @rule required
     */
    public $amount;

    /**
     * @var int
     * @rule required
     * @scenario
     */
    public $default = 333;

    /**
     * @var int
     * @rule range 10, 20
     * @scenario
     * @scenario TEST
     */
    public $ten_twenty;

    /**
     * @var int
     * @rule required
     * @rule range 20, 30
     * @scenario TEST
     */
    public $twenty_thirty;

    /**
     * @var string
     * @rule email
     * @rule matchDomain "karmabunny.com.au"
     */
    public $address;

    /** @var string */
    #[Rule('required')]
    #[Rule('email')]
    #[Rule('length', 5, 25)]
    #[Rule('matchDomain', 'example.com')]
    public $php8;

    /**
     * @var string
     */
    #[Rule('required')]
    #[Scenario()]
    #[Scenario(self::SCENARIO_TEST)]
    public $php8_scenario;


    public static function matchDomain($value, string $domain)
    {
        $re = '/@' . preg_quote($domain) . '$/';

        if (!preg_match($re, $value)) {
            throw new ValidationException("Domain must be '{$domain}'.");
        }
    }
}
