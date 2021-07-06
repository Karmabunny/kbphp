<?php

namespace karmabunny\kb;


/**
 * CLI prompt helpers.
 *
 * This relies heavily on VT100 escapes.
 *
 * Uses the 'readline' extension when available.
 *
 * http://www.climagic.org/mirrors/VT100_Escape_Codes.html
 *
 * @package karmabunny\kb
 */
class Cli
{

    const KEY_UP    = "\u{001b}[A";
    const KEY_DOWN  = "\u{001b}[B";
    const KEY_RIGHT = "\u{001b}[C";
    const KEY_LEFT  = "\u{001b}[D";


    /**
     * The readline extension is loaded.
     *
     * @return bool
     */
    protected static function hasReadline(): bool
    {
        static $yes;
        if ($yes === null) {
            $yes = extension_loaded('readline');
        }
        return $yes;
    }


    /**
     * Clear the screen.
     *
     * @return void
     */
    public static function clear()
    {
        // Maaaagic.
        fwrite(STDOUT, chr(27) . "[H" . chr(27) . "[2J");
    }


    /**
     * Clear the current line.
     *
     * @return void
     */
    public static function clearLine()
    {
        fwrite(STDOUT, chr(27) . '[2K' . "\r");
    }


    /**
     * Get regular input.
     *
     * @param string|null $prompt
     * @return string
     */
    public static function input(string $prompt = null): string
    {
        if (self::hasReadline()) {
            return readline($prompt . ': ');
        }

        if ($prompt) {
            echo $prompt . ': ';
        }

        return fgets(STDIN);
    }


    /**
     * Ask a yes/no question. Accept nothing else.
     *
     * @param string $text
     * @return string
     */
    public static function question(string $text): bool
    {
        $matches = [];

        while (true) {
            $out = self::input(trim($text . ' (yes/no)'));

            if (!preg_match('/^(no?|ye?s?)/i', $out, $matches)) {
                echo "Invalid input, please specify 'yes' or 'no'.", PHP_EOL;
                continue;
            }

            break;
        }

        return stripos($matches[0], 'y') === 0;
    }


    /**
     * Get an option from a list.
     *
     * Use the up/down keys to choose an option.
     *
     * @param string|null $prompt
     * @param string[] $options
     * @return string
     */
    public static function options(string $prompt, array $options)
    {
        try {
            system('stty cbreak');
            system('stty -echo');

            $first = Arrays::firstKey($options);
            $last = Arrays::lastKey($options);
            $current = key($options);

            while (true) {
                // Clear and prompt.
                self::clearLine();
                echo $prompt . ': ' . $options[$current];

                $char = fread(STDIN, 4);

                // Choose one!.
                if ($char == "\n") {
                    return $options[$current];
                }

                // Option up.
                if ($char == self::KEY_UP and $current != $first) {
                    prev($options);
                    $current = key($options);
                    continue;
                }

                // Option down.
                if ($char == self::KEY_DOWN and $current != $last) {
                    next($options);
                    $current = key($options);
                    continue;
                }
            }
        }
        finally {
            echo PHP_EOL;
            system('stty -cbreak');
            system('stty echo');
        }
    }


    /**
     * Get input, but the echo is masked.
     *
     * @param string|null $prompt
     * @return string
     */
    public static function masked(string $prompt = null): string
    {
        if ($prompt) {
            echo $prompt . ': ';
        }

        try {
            system('stty cbreak');
            system('stty -echo');

            $buffer = '';

            while (true) {
                $char = fread(STDIN, 4);

                // Done!
                if ($char == chr(10)) {
                    return $buffer;
                }

                // Backspaces.
                if ($char == chr(127)) {
                    if (strlen($buffer)) {
                        echo chr(8) . ' ' . chr(8);
                    }
                    $buffer = mb_substr($buffer, 0, -1);
                    continue;
                }

                // Print masks.
                echo "*";
                $buffer .= $char;
            }

            return $buffer;
        }
        finally {
            echo PHP_EOL;
            system('stty -cbreak');
            system('stty echo');
        }
    }


    /**
     * Get input, but no echo.
     *
     * @param string|null $prompt
     * @return string
     */
    public static function invisible(string $prompt = null): string
    {
        if ($prompt) {
            echo $prompt . ': ';
        }

        try {
            system('stty -echo');
            return trim(fgets(STDIN), "\n");
        }
        finally {
            echo PHP_EOL;
            system('stty echo');
        }
    }

}