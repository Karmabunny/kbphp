<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\RulesValidator;
use karmabunny\kb\Validity;
use PHPUnit\Framework\TestCase;


class ValidatorTest extends TestCase
{

    public function dataCheckFailures() {
        return [
            ['aa', 'email'],
            ['@example.com', 'email'],
            ['test@', 'email'],
            ['A', 'length', 2],
            ['A', 'length', 2, 3],
            ['A', 'positiveInt'],
            ['B', [Validity::class, 'positiveInt']],
        ];
    }

    /**
    * @dataProvider dataCheckFailures
    */
    public function testCheckFailures($value, $func)
    {
        $validator = new RulesValidator(['field' => $value]);

        // Awful hack to pass through varargs to the Validator::check method
        $args = func_get_args();
        $args[0] = 'field';
        call_user_func_array([$validator, 'check'], $args);

        $this->assertTrue($validator->hasErrors());
        $this->assertCount(1, $validator->getFieldErrors());
        $this->assertArrayHasKey('field', $validator->getFieldErrors());
        $errs = $validator->getFieldErrors();
        $this->assertCount(1, $errs['field']);
    }

    public function testArrayCheck()
    {
        $data = ['vals' => [1, 2, 'A', 'B', 5]];
        $validator = new RulesValidator($data);

        $results = $validator->arrayCheck('vals', 'positiveInt');

        $this->assertCount(count($data['vals']), $results);
        $this->assertTrue($results[0]);
        $this->assertTrue($results[1]);
        $this->assertFalse($results[2]);
        $this->assertFalse($results[3]);
        $this->assertTrue($results[4]);

        $this->assertTrue($validator->hasErrors());
        $this->assertCount(1, $validator->getFieldErrors());
        $this->assertArrayHasKey('vals', $validator->getFieldErrors());

        $errs = $validator->getFieldErrors();
        $this->assertCount(2, $errs['vals']);
        $this->assertArrayHasKey(2, $errs['vals']);
        $this->assertCount(1, $errs['vals'][2]);
        $this->assertArrayHasKey(3, $errs['vals']);
        $this->assertCount(1, $errs['vals'][3]);
    }


    public function testArrayCheckCallable()
    {
        $data = ['vals' => [1, 2, 'A', 'B', 5]];
        $validator = new RulesValidator($data);

        $results = $validator->arrayCheck('vals', [Validity::class, 'positiveInt']);

        $this->assertCount(count($data['vals']), $results);
    }
}
