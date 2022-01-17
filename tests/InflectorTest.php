<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

use karmabunny\kb\Inflector;
use PHPUnit\Framework\TestCase;

/**
 * Test the Inflector helper.
 */
final class InflectorTest extends TestCase {

    public function testUncountable()
    {
        $inflector = new Inflector();

        $expected = 'access';

        $actual = $inflector->plural('access');
        $this->assertEquals($expected, $actual);

        $actual = $inflector->singular('access');
        $this->assertEquals($expected, $actual);

        $expected = 'work';

        $actual = $inflector->plural('work');
        $this->assertEquals($expected, $actual);

        $actual = $inflector->singular('work');
        $this->assertEquals($expected, $actual);
    }


    public function testIrregular()
    {
        $inflector = new Inflector();

        $expected = 'children';

        $actual = $inflector->plural('child');
        $this->assertEquals($expected, $actual);

        // TODO It'd be cool if this already knew this was a plural.
        // Currently it returns 'childrens' - with an 's' suffix.
        // $actual = $inflector->plural('children');
        // $this->assertEquals($expected, $actual);

        $expected = 'child';

        $actual = $inflector->singular('children');
        $this->assertEquals($expected, $actual);

        $actual = $inflector->singular('child');
        $this->assertEquals($expected, $actual);
    }


    public function testPlurals()
    {
        $inflector = new Inflector();

        // 's' suffix.
        $expected = 'keyboards';
        $actual = $inflector->plural('keyboard');

        $this->assertEquals($expected, $actual);

        // 'es' suffix.
        $expected = 'matches';
        $actual = $inflector->plural('match');

        $this->assertEquals($expected, $actual);

        // '[h]s' suffix.
        $expected = 'baths';
        $actual = $inflector->plural('bath');

        $this->assertEquals($expected, $actual);

        // 'ies' suffix.
        $expected = 'skies';
        $actual = $inflector->plural('sky');

        $this->assertEquals($expected, $actual);

        // 's' suffix, but not 'es'.
        $expected = 'pays';
        $actual = $inflector->plural('pay');

        $this->assertEquals($expected, $actual);
    }


    public function testSingulars()
    {
        $inflector = new Inflector();

        // 's' suffix.
        $expected = 'keyboard';
        $actual = $inflector->singular('keyboards');

        $this->assertEquals($expected, $actual);

        // '[s]es' suffix.
        $expected = 'posies';
        $actual = $inflector->singular('posy');

        // '[t]hes' suffix.
        $expected = 'match';
        $actual = $inflector->singular('matches');

        $this->assertEquals($expected, $actual);

        // '[t]hs' suffix.
        $expected = 'bath';
        $actual = $inflector->singular('baths');

        $this->assertEquals($expected, $actual);

        // 'ies' suffix.
        $expected = 'sky';
        $actual = $inflector->singular('skies');

        $this->assertEquals($expected, $actual);

        // not 'ss' suffixes.
        $expected = 'bliss';
        $actual = $inflector->singular('bliss');

        $this->assertEquals($expected, $actual);

        // 's' suffix.
        $expected = 'hit';
        $actual = $inflector->singular('hits');

        $this->assertEquals($expected, $actual);
    }


    public function testCamelCase()
    {
        // Basic.
        $expected = 'this is camel case';
        $actual = Inflector::humanize('ThisIsCamelCase');

        $this->assertEquals($expected, $actual);

        // Normalizing.
        $expected = 'ThisIsCamelCase';
        $actual = Inflector::camelize('thisIsCamelCase  ');

        $this->assertEquals($expected, $actual);

        $expected = 'this_is_camel_case';
        $actual = Inflector::underscore('ThisIsCamelCase');

        $this->assertEquals($expected, $actual);

        $expected = 'this-is-camel-case';
        $actual = Inflector::kebab('ThisIsCamelCase');

        $this->assertEquals($expected, $actual);
    }


    public function testUnderscore()
    {
        // Basic.
        $expected = 'this is underscore';
        $actual = Inflector::humanize('this_is_underscore');

        $this->assertEquals($expected, $actual);

        // Normalizing.
        $expected = 'this_is_underscore';
        $actual = Inflector::underscore('_this_is_underscore');

        $this->assertEquals($expected, $actual);

        $expected = 'ThisIsUnderscore';
        $actual = Inflector::camelize('this_is_underscore');

        $this->assertEquals($expected, $actual);

        $expected = 'this-is-underscore';
        $actual = Inflector::kebab('this_is_underscore');

        $this->assertEquals($expected, $actual);
    }


    public function testKebab()
    {
        // Basic.
        $expected = 'this is kebab';
        $actual = Inflector::humanize('this-is-kebab');

        $this->assertEquals($expected, $actual);

        // Normalizing.
        $expected = 'this-is-kebab';
        $actual = Inflector::kebab('  this-is-kebab--');

        $this->assertEquals($expected, $actual);

        $expected = 'ThisIsKebab';
        $actual = Inflector::camelize('this-is-kebab');

        $this->assertEquals($expected, $actual);

        $expected = 'this_is_kebab';
        $actual = Inflector::underscore('this-is-kebab');

        $this->assertEquals($expected, $actual);
    }
}
