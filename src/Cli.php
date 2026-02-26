<?php
declare(strict_types=1);

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
     *
     * @param mixed $args
     * @return void
     */
    public static function puts(mixed ...$args): void
    {
        self::stdout(implode(' ', $args) . PHP_EOL);
    }


    /**
     *
     * @param mixed $args
     * @return void
     */
    public static function error(mixed ...$args): void
    {
        self::stderr(implode(' ', $args) . PHP_EOL);
    }


    /**
     *
     * @param mixed $text
     * @return void
     */
    public static function stdout(mixed $text): void
    {
        fwrite(\STDOUT, $text);
    }


    /**
     *
     * @param mixed $text
     * @return void
     */
    public static function stderr(mixed $text): void
    {
        fwrite(\STDERR, $text);
    }


    /**
     * The readline extension is loaded.
     *
     * @return bool
     */
    public static function hasReadline(): bool
    {
        static $yes;
        if ($yes === null) {
            $yes = extension_loaded('readline');
        }
        return $yes;
    }


    /**
     * Is the host terminal a TTY.
     *
     * This is required for interactive input.
     *
     * @return bool
     */
    public static function hasTTY(): bool
    {
        static $yes;
        if ($yes === null) {
            $yes = (posix_isatty(STDIN) and posix_isatty(STDOUT));
        }
        return $yes;
    }


    /**
     *
     * @return void
     */
    protected static function registerExits(): void
    {
        static $registered = false;
        if ($registered) return;
        $registered = true;

        $reset = function(int $signal) {
            system('stty -cbreak');
            system('stty echo');

            switch ($signal) {
                case SIGINT:
                    echo "\nInterrupted\n";
                    exit(2);

                case SIGTERM:
                    echo "\nTerminated\n";
                    exit(2);

                case SIGQUIT:
                    echo "\nQuit\n";
                    exit(2);

                case SIGHUP:
                    echo "\nHangup\n";
                    exit(2);

                default:
                    echo "\nCaught: {$signal}\n";
                    exit(2);
            }
        };

        if (function_exists('pcntl_async_signals')) {
            // phpcs:ignore
            pcntl_async_signals(true);
        }

        pcntl_signal(SIGINT, $reset);
        pcntl_signal(SIGTERM, $reset);
        pcntl_signal(SIGQUIT, $reset);
        pcntl_signal(SIGHUP, $reset);
    }


    /**
     * Clear the screen.
     *
     * @return void
     */
    public static function clear(): void
    {
        // Maaaagic.
        fwrite(STDOUT, chr(27) . "[H" . chr(27) . "[2J");
    }


    /**
     * Clear the current line.
     *
     * @return void
     */
    public static function clearLine(): void
    {
        fwrite(STDOUT, chr(27) . '[2K' . "\r");
    }


    /**
     * Get regular input.
     *
     * @param string|null $prompt
     * @return string
     */
    public static function input(?string $prompt = null): string
    {
        if (self::hasReadline()) {
            return readline($prompt ? $prompt . ': ' : null);
        }

        if ($prompt) {
            echo $prompt . ': ';
        }

        return fgets(STDIN);
    }


    /**
     * Ask a yes/no question. Accept nothing else.
     *
     * @param string $prompt
     * @return bool
     */
    public static function question(string $prompt): bool
    {
        $matches = [];

        while (true) {
            $out = self::input(trim($prompt . ' (yes/no)'));

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
     * @param string $prompt
     * @param string[] $options
     * @return string
     */
    public static function options(string $prompt, array $options): string
    {
        self::registerExits();

        try {
            system('stty cbreak');
            system('stty -echo');

            $first = Arrays::firstKey($options);
            $last = Arrays::lastKey($options);
            $current = key($options);

            $total = count($options);
            $keys = array_flip(array_keys($options));

            while (true) {
                // Clear and prompt.
                self::clearLine();
                $index = $keys[$current] + 1;
                echo "{$prompt} ({$index}/{$total}): " . $options[$current];

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
    public static function masked(?string $prompt = null): string
    {
        self::registerExits();

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
                    break;
                }

                // Skip these.
                if (
                    $char == self::KEY_UP or
                    $char == self::KEY_DOWN or
                    $char == self::KEY_LEFT or
                    $char == self::KEY_RIGHT
                ) continue;

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
    public static function invisible(?string $prompt = null): string
    {
        self::registerExits();

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