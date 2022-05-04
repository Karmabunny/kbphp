<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Text;
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
}
