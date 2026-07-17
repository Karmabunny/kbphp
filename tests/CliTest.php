<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

use karmabunny\kb\Cli;
use PHPUnit\Framework\TestCase;

/**
 * Test the Cli helpers.
 */
final class CliTest extends TestCase
{

    public function testJoinAnsi()
    {
        // Plain text with separator.
        $actual = Cli::joinAnsi(' ', ['one', 'two', 'three']);
        $this->assertEquals('one two three', $actual);

        // Empty separator is a plain implode.
        $actual = Cli::joinAnsi('', [Cli::FG_RED, 'hello', Cli::RESET]);
        $this->assertEquals(Cli::FG_RED . 'hello' . Cli::RESET, $actual);

        // ANSI codes stick to the following text, no separator between them.
        $actual = Cli::joinAnsi(' ', [Cli::FG_RED, Cli::BOLD, 'hello', 'world', Cli::RESET]);
        $this->assertEquals(Cli::FG_RED . Cli::BOLD . 'hello world' . Cli::RESET, $actual);

        // Trailing ANSI codes are kept.
        $actual = Cli::joinAnsi(' ', ['hello', Cli::RESET]);
        $this->assertEquals('hello' . Cli::RESET, $actual);
    }


    public function testStripAnsi()
    {
        // No ANSI codes.
        $this->assertEquals('hello', Cli::stripAnsi('hello'));

        // Single colour code.
        $actual = Cli::stripAnsi(Cli::FG_RED . 'hello' . Cli::RESET);
        $this->assertEquals('hello', $actual);

        // Mixed codes and text.
        $actual = Cli::stripAnsi(Cli::FG_GREEN . Cli::BOLD . 'hello' . Cli::RESET . ' world');
        $this->assertEquals('hello world', $actual);
    }
}
