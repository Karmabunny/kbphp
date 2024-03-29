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
     * Create a command object.
     *
     * This _begins_ executing a shell command, but then it's your
     * responsibility to process the output (with read/readAll) and close the
     * handle.
     *
     * Note, `proc_close()` is automatically called on shutdown.
     *
     * Use this for increased flexibility:
     * - pipe stdout/stderr directly to files or sockets
     * - pipe or wrote to the stdin
     * - read the output line by line and stream it somewhere
     * - read the exit code
     * - terminate a command
     *
     * @param string|array|ShellOptions $config
     *   - cmd  - `string`
     *   - args - `string[]`
     *   - cwd  - `string`
     *   - env  - `string[]`
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
