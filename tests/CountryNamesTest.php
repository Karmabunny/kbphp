<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\CountryNames;
use PHPUnit\Framework\TestCase;

/**
 * Test the CountryNames class.
 */
final class CountryNamesTest extends TestCase
{
    public function testAlphaCodes()
    {
        // It's a lazy test but it doesn't explode so that's cool I guess.

        $alpha2 = CountryNames::getAlpha2();
        $this->assertContains('AU', $alpha2);
        $this->assertContains('US', $alpha2);
        $this->assertNotContains('AUS', $alpha2);
        $this->assertNotContains('USA', $alpha2);

        $alpha3 = CountryNames::getAlpha3();
        $this->assertContains('AUS', $alpha3);
        $this->assertContains('USA', $alpha3);
        $this->assertNotContains('AU', $alpha3);
        $this->assertNotContains('US', $alpha3);

        $expected = 'AUS';
        $actual = CountryNames::getAlpha3From2('AU');
        $this->assertEquals($expected, $actual);

        $expected = 'US';
        $actual = CountryNames::getAlpha2From3('USA');
        $this->assertEquals($expected, $actual);
    }


    public function testCountryName()
    {
        $locales = \ResourceBundle::getLocales('');
        $locales = array_fill_keys($locales, true);

        $tests = 0;

        if (!empty($locales['en'])) {
            $tests++;

            // Alpha3 English
            $name = CountryNames::getCountryName('aus', 'en');
            $this->assertEquals('Australia', $name);

            // Alpha2 English
            $name = CountryNames::getCountryName('au', 'en');
            $this->assertEquals('Australia', $name);
        }

        if (!empty($locales['fr'])) {
            $tests++;

            // Alpha3 French
            $name = CountryNames::getCountryName('aus', 'fr');
            $this->assertEquals('Australie', $name);

            // Alpha2 French
            $name = CountryNames::getCountryName('au', 'fr');
            $this->assertEquals('Australie', $name);
        }

        if (!empty($locales['zh'])) {
            $tests++;

            // Alpha3 Chinese
            $name = CountryNames::getCountryName('aus', 'zh');
            $this->assertEquals('澳大利亚', $name);

            // Alpha2 Chinese
            $name = CountryNames::getCountryName('au', 'zh');
            $this->assertEquals('澳大利亚', $name);
        }

        if (!$tests) {
            $this->markTestSkipped('No locales found');
        }
    }


    public function testCountryNameList()
    {
        // Also lazy, but also not explody.
        $alpha3 = CountryNames::getAlpha3();
        $countries = CountryNames::getCountryNameList();
        $this->assertEquals(count($alpha3), count($countries));
    }


    public function testCountryCode()
    {
        // English
        $countries = CountryNames::getCountryNameList('en');

        foreach ($countries as $expected => $name) {
            $actual = CountryNames::getCountryCode($name, 'en');
            $this->assertEquals($expected, $actual);
        }

        // Chinese
        // This skips the shorthand lookup.
        $countries = CountryNames::getCountryNameList('zh');

        foreach ($countries as $expected => $name) {
            $actual = CountryNames::getCountryCode($name, 'zh');
            $this->assertEquals($expected, $actual);
        }
    }
}
