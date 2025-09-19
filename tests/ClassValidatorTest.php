<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\RulesClassValidator;
use PHPUnit\Framework\TestCase;


class ClassValidatorTest extends TestCase
{

    public function dataRules()
    {
        return [
            // Test 1 field.
            'required' => [
                ['field'],
                [],
                ['field' => 'something'],
            ],
            // Test 2 fields.
            'oneRequired:1' => [
                ['field1', 'field2'],
                ['field1' => '', 'field2' => ''],
                ['field1' => 'something', 'field2' => ''],
            ],
            // Test 3+ fields.
            'oneRequired:3' => [
                ['field1', 'field2', 'field3'],
                ['field1' => '', 'field2' => '', 'field3' => ''],
                ['field1' => '', 'field2' => '', 'field3' => 'something'],
            ],
            // Test arrays.
            'oneRequired:4' => [
                ['field1', 'field2'],
                ['field1' => [], 'field2' => []],
                ['field1' => [], 'field2' => ['']],
            ],
            // Test missing key.
            'oneRequired:5' => [
                ['field1', 'field2'],
                ['field2' => ''],
                ['field2' => 'something'],
            ],
        ];
    }


    public function dataFailure()
    {
        $data = [];
        foreach ($this->dataRules() as $name => $item) {
            [$rule] = explode(':', $name, 2);
            $data[$name] = [ $rule, $item[0], $item[1] ];
        }
        return $data;
    }


    public function dataSuccess()
    {
        $data = [];
        foreach ($this->dataRules() as $name => $item) {
            [$rule] = explode(':', $name, 2);
            $data[$name] = [ $rule, $item[0], $item[2] ];
        }
        return $data;
    }


    /** @dataProvider dataFailure */
    public function testFailure($rule, $config, $data)
    {
        $validator = new RulesClassValidator($data);
        $validator->setRules([$rule => $config]);

        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors());

        foreach ($data as $field => $value) {
            $this->assertArrayHasKey($field, $validator->getErrors());
        }
    }


    /** @dataProvider dataSuccess */
    public function testSuccess($rule, $config, $data)
    {
        $validator = new RulesClassValidator($data);
        $validator->setRules([$rule => $config]);

        $this->assertTrue($validator->validate());
        $this->assertFalse($validator->hasErrors());
        $this->assertEmpty($validator->getErrors());
    }
}
