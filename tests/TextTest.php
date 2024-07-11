<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Text;
use karmabunny\kb\TextMaskTypes;
use PHPUnit\Framework\TestCase;

/**
 * Test the Text helpers.
 */
final class TextTest extends TestCase
{

    public function testNormalize()
    {
        // Nothing. This is literally just a passthrough.
        // No-one would use this directly (instead via compare/similar).
        $expected = 'Nôtr3';
        $actual = Text::normalize($expected, 0);
        $this->assertEquals($expected, $actual);

        // Just multi-byte.
        $actual1 = Text::normalize('nôtre', Text::NORMALIZE_MULTIBYTE);
        $this->assertNotEquals('nôtre', $actual1);

        // Lowercase + multi-byte.
        $actual2 = Text::normalize('Nôtre', Text::NORMALIZE_CASE | Text::NORMALIZE_MULTIBYTE);
        $this->assertEquals($actual1, $actual2);

        // Lowercase + multi-byte + alpha magic.
        $actual3 = Text::normalize('Nôtr3', Text::NORMALIZE_ALPHA | Text::NORMALIZE_CASE | Text::NORMALIZE_MULTIBYTE);
        $this->assertEquals($actual1, $actual3);
    }


    public function testMultibyte()
    {
        $worst = Text::similarity('Nôtre', 'Notre', 0);
        $best = Text::similarity('Nôtre', 'Notre', Text::NORMALIZE_MULTIBYTE);

        $this->assertGreaterThanOrEqual($worst, $best);

        $worst = Text::compare('Nôtre', 'Notre', 0);
        $best = Text::compare('Nôtre', 'Notre', Text::NORMALIZE_MULTIBYTE);

        $this->assertLessThanOrEqual($worst, $best);
    }


    public function testSimilarity()
    {
        $data = [
            // One to one.
            ['Jishnu Praday', 'JISHNU PRADAY', true],

            // Typo.
            ['Mishele Jane', 'MISHELLE JANE', true],

            // Special characters.
            ['NOTRE DAME', 'Nôtre Dame', true],

            // Genuinely different names.
            ['Mitchell John', 'MISHELLE JANE', false],

            // Obviously no.
            ['Test Test', 'MISHELLE JANE', false],

            // Too long.
            ['Mishelle AAAA', 'MISHELLE ' . str_repeat('a', 256), false],
        ];

        foreach ($data as [$str1, $str2, $expected]) {
            $actual = Text::similar($str1, $str2);
            $this->assertEquals($expected, $actual, "'{$str1}' like '{$str2}'");
        }
    }


    public function testFind()
    {
        $options = [
            'db/migrate',
            'modules/cron/update-logs',
            'modules/cron/delete-files',
            'modules/cron/delete-logs',
            'db/seed',
            'db/feed',
        ];

        // Exact match.
        $actual = Text::find('db/seed', $options);
        $expected = [ 'db/seed'];
        $this->assertEquals($expected, $actual);

        // Too many characters?
        $actual = Text::find('db/seeed', $options);
        $expected = [ 'db/seed' ];

        $this->assertEquals($expected, $actual);

        // Short string only get a max 1 or 2 distance.
        $actual = Text::find('db/deed', $options);
        $expected = [
            'db/seed',
            'db/feed',
        ];

        $this->assertEquals($expected, $actual);

        // Longer strings get more leeway.
        $actual = Text::find('modules/cron/uppete-logs', $options);
        $expected = [
            'modules/cron/update-logs',
            'modules/cron/delete-logs',
        ];
        $this->assertEquals($expected, $actual);

        // But not _too_ much leeway.
        $actual = Text::find('modules/cron/update-files', $options);
        $expected = [ 'modules/cron/update-logs' ];

        $this->assertEquals($expected, $actual);

        // Can also add a flag for 'starts with'.
        $flags = Text::NORMALIZE_ALL;
        $flags |= Text::FIND_STARTS_WITH;

        $actual = Text::find('modules', $options, 5, $flags);
        $expected = [
            'modules/cron/update-logs',
            'modules/cron/delete-files',
            'modules/cron/delete-logs',
        ];

        $this->assertEquals($expected, $actual);

        // It can handle typos too.
        // Also has a shorthand.
        $actual = Text::startsWith('moodles/cron', $options);
        $expected = [
            'modules/cron/update-logs',
            'modules/cron/delete-files',
            'modules/cron/delete-logs',
        ];
        $this->assertEquals($expected, $actual);
    }


    public function testMask()
    {
        $word = 'áδćďê';

        $actual = Text::mask($word);
        $this->assertEquals('*****', $actual);

        $actual = Text::mask($word, '*', 1, 0);
        $this->assertEquals('á****', $actual);

        $actual = Text::mask($word, '*', 0, 1);
        $this->assertEquals('****ê', $actual);

        $actual = Text::mask($word, '*', 1, 1);
        $this->assertEquals('á***ê', $actual);

        $actual = Text::mask($word, '*', 20, 0);
        $this->assertEquals('*****', $actual);

        $actual = Text::mask($word, '*', -20, 0);
        $this->assertEquals('*****', $actual);

        $actual = Text::mask($word, '*', 0, 20);
        $this->assertEquals('*****', $actual);

        $actual = Text::mask($word, '*', 0, -20);
        $this->assertEquals('*****', $actual);

        $actual = Text::mask($word, '*', -20, -20);
        $this->assertEquals('*****', $actual);
    }


    public function testMaskPreset()
    {
        $actual = Text::maskPreset('Jo Anne', '*', TextMaskTypes::FirstName);
        $this->assertEquals('J* ****', $actual);

        $actual = Text::maskPreset('Van Helsing', '*', TextMaskTypes::LastName);
        $this->assertEquals('*** ******g', $actual);

        $actual = Text::maskPreset('Jo Anne Van Helsing', '*', TextMaskTypes::FirstLasName);
        $this->assertEquals('J* **** *** ******g', $actual);

        $actual = Text::maskPreset('email@domain.com', '*', TextMaskTypes::Email);
        $this->assertEquals('e***l@d********m', $actual);

        $actual = Text::maskPreset('0403123123', '*', TextMaskTypes::Phone);
        $this->assertEquals('*******123', $actual);

        $actual = Text::maskPreset('Quick down fox', '*', TextMaskTypes::Spaces);
        $this->assertEquals('***** **** ***', $actual);
    }
}
