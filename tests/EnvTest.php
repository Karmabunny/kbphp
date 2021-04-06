<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Env;
use PHPUnit\Framework\TestCase;

/**
 * Test the Environment helper.
 */
final class EnvTest extends TestCase
{

    public function testSystemLoad()
    {
        // A single system env variable.
        $_SERVER['TEST'] = 'THIS';
        putenv('TEST=THIS');

        // Clean and load.
        Env::$config = null;
        Env::loadFromSystem();

        // Test a real one.
        $actual = Env::get('TEST');
        $expected = 'THIS';
        $this->assertEquals($expected, $actual);

        // Test a fake one.
        $actual = Env::get('MISSING');
        $this->assertNull($actual);
    }


    public function testFileLoad()
    {
        // Some bogus system stuff.
        $_SERVER['TEST'] = 'THIS';
        putenv('TEST=THIS');

        // Clean and load.
        Env::$config = null;
        Env::loadFromFile(__DIR__ . '/test.env');

        // System one doesn't exist.
        $actual = Env::get('TEST');
        $this->assertNull($actual);

        $actual = Env::get('MISSING');
        $this->assertNull($actual);

        $actual = Env::get('FILE_TEST');
        $expected = 'HELLO WORLD';
        $this->assertEquals($expected, $actual);

        $actual = Env::get('WITH_SPACES');
        $expected = 'abcd # not an inline comment';
        $this->assertEquals($expected, $actual);

        $actual = Env::get('WITH_QUOTES');
        $expected = '1234  ';
        $this->assertEquals($expected, $actual);

        // Not valid.
        $actual = Env::get('# invalid');
        $this->assertNull($actual);

        // Also not valid.
        $actual = Env::get('');
        $this->assertNull($actual);

        // Just to be sure.
        $this->assertCount(3, Env::$config);
    }


    public function testShorthand()
    {
        Env::$config = [];

        // Also testing the configure here.
        Env::config([
            'ENV_NAME' => 'env',
            'DEFAULT' => 'prod',
        ]);

        // No env, default is 'prod'.
        $actual = Env::env([
            'dev' => 'abc',
            'test' => 'def',
            'prod' => 'ghi',
        ]);
        $expected = 'ghi';
        $this->assertEquals($expected, $actual);

        // Straight up invalid, use env default (prod).
        Env::$config['env'] = 'bad';

        $actual = Env::env([
            'dev' => 'abc',
            'test' => 'def',
            'prod' => 'ghi',
        ]);
        $expected = 'ghi';
        $this->assertEquals($expected, $actual);

        // dev is dev, cool.
        Env::$config['env'] = 'dev';

        $actual = Env::env([
            'dev' => 'abc',
            'test' => 'def',
            'prod' => 'ghi',
        ]);
        $expected = 'abc';
        $this->assertEquals($expected, $actual);

        // Slightly dodge name but close enough.
        Env::$config['env'] = 'testing';

        $actual = Env::env([
            'dev' => 'abc',
            'test' => 'def',
            'prod' => 'ghi',
        ]);
        $expected = 'def';
        $this->assertEquals($expected, $actual);

        // Missing 'qa', the default is then the 'prod' value.
        Env::$config['env'] = 'qa';

        $actual = Env::env([
            'dev' => 'abc',
            'test' => 'def',
            'prod' => 'ghi',
        ]);
        $expected = 'ghi';
        $this->assertEquals($expected, $actual);

        // Missing 'qa' +  default 'prod', the default is then first value.
        Env::$config['env'] = 'qa';

        $actual = Env::env([
            'dev' => 'abc',
            'test' => 'def',
        ]);
        $expected = 'abc';
        $this->assertEquals($expected, $actual);
    }


    public function testEnvironment()
    {
        // No env.
        Env::$config = [];

        $actual = Env::environment();
        $expected = Env::$DEFAULT;
        $this->assertEquals($expected, $actual);

        // Production.
        Env::$config[Env::$ENV_NAME] = 'production';

        $actual = Env::environment();
        $expected = Env::PROD;
        $this->assertEquals($expected, $actual);

        // PHPUNIT special case.
        define('PHPUNIT', 1);

        $actual = Env::environment();
        $expected = Env::TEST;
        $this->assertEquals($expected, $actual);
    }
}