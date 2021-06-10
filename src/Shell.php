<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use Generator;

/**
 * Utilities for doing shell things.
 */
class Shell
{

    /**
     * Safely interpolate command arguments.
     *
     * Use an 'args' array like:
     *
     * ```
     * // 'ping {0}'
     * ['google.com']
     *
     * // 'ping {ip}'
     * ['ip' => 'google.com']
     * ```
     *
     * Note, this _does not_ escape the command components. However, I can't
     * imagine why you would give that control to a user.
     * If you have or even plan to - you should be shot.
     *
     * @param string $cmd template string
     * @param array $args keyed args
     * @return string escaped cmd string
     */
    public static function escape(string $cmd, array $args)
    {
        return preg_replace_callback('/{([^}]+)}/', function($matches) use ($args) {
            $index = $matches[1];
            return escapeshellarg($args[$index] ?? '');
        }, $cmd);
    }


    /**
     * Execute a command as a whole block.
     *
     * This function returns the output as big fat string.
     *
     * @param string $dir
     * @param string $cmd
     * @param mixed $args
     * @return string
     */
    public static function runSync(string $dir, string $cmd, ...$args)
    {
        $shell = self::run([
            'cwd' => $dir,
            'cmd' => $cmd,
            'args' => $args,
        ]);

        $output = $shell->readAll();
        $shell->close();
        return $output;
    }


    /**
     * Execute a command and yield each line.
     *
     * The function returns an iterable (generator) of output. Permitting one
     * to loop over the output and procedurally do whatever they please with it.
     *
     * @param string $dir
     * @param string $cmd
     * @param mixed $args
     * @return Generator<string>
     */
    public static function runAsync(string $dir, string $cmd, ...$args)
    {
        $shell = self::run([
            'cwd' => $dir,
            'cmd' => $cmd,
            'args' => $args,
        ]);

        yield from $shell->read();
        return $shell->close();
    }


    /**
     *
     * @param string|array|ShellOptions $config
     * @return ShellOutput
     */
    public static function run($config)
    {
        $config = ShellOptions::parse($config);

        $cmd = $config->getCommand();
        $descriptors = $config->getDescriptors();

        $pipes = [];
        $handle = proc_open($cmd, $descriptors, $pipes, $config->cwd, $config->env);
        return new ShellOutput($config, $handle, $pipes);
    }
}


class ShellOptions extends Collection
{
    /** @var string required */
    public $cmd;

    /** @var array */
    public $args = [];

    /** @var string|null */
    public $cwd = null;

    /** @var string[] */
    public $env = [];

    /** @var string|array|resource */
    public $stdout = 'pipe';

    /** @var string|array|resource */
    public $stderr = 'pipe';

    /** @var int Limit for fgets() in bytes. */
    public $chunk_size = 1024;


    /**
     *
     * @param string|array|ShellOptions $config
     * @return ShellOptions
     */
    public static function parse($config)
    {
        if ($config instanceof self) {
            return clone $config;
        }

        if (is_string($config)) {
            return new self(['cmd' => $config]);
        }

        return new self($config);
    }


    /**
     *
     * @return array
     */
    public function getDescriptors(): array
    {
        return [
            0 => ['pipe', 'r'],
            1 => self::parseDescriptor($this->stdout),
            2 => self::parseDescriptor($this->stderr),
        ];
    }


    /**
     *
     * @return string
     */
    public function getCommand(): string
    {
        return Shell::escape($this->cwd, $this->args);
    }


    private static function parseDescriptor($descriptor)
    {
        if (is_resource($descriptor)) {
            return $descriptor;
        }

        if (is_array($descriptor)) {
            return $descriptor;
        }

        if ($descriptor === 'pipe') {
            return ['pipe', 'w'];
        }

        if (is_string($descriptor)) {
            return ['file', $descriptor, 'a'];
        }

        return ['pipe', 'w'];
    }

}
