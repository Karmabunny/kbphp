<?php
namespace karmabunny\kb;

use Exception;
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



class ShellOutput
{

    const STREAM_STDIN = 0;
    const STREAM_STDOUT = 1;
    const STREAM_STDERR = 2;
    const STREAM_ALL = 3;


    /** @var ShellOptions */
    public $config;

    /** @var resource */
    public $handle;

    /** @var int */
    public $pid;

    /** @var bool */
    public $running;

    /** @var int|false */
    public $exit;

    /** @var array */
    private $pipes;

    /** @var array */
    private $descriptors;


    /**
     *
     * @param ShellOptions $config
     * @param resource $handle from proc_open()
     * @param array $pipes from proc_open()
     */
    public function __construct(ShellOptions $config, $handle, array $pipes)
    {
        $this->config = $config;
        $this->handle = $handle;
        $this->pipes = $pipes;

        $status = proc_get_status($handle);
        $this->pid = $status['pid'];
        $this->running = $status['running'];
        $this->exit = $status['exitcode'];

        $this->descriptors = $config->getDescriptors();

        if ($this->getTarget(self::STREAM_STDOUT) === 'pipe') {
            stream_set_blocking($pipes[1], false);
        }

        if ($this->getTarget(self::STREAM_STDERR) === 'pipe') {
            stream_set_blocking($pipes[2], false);
        }

        register_shutdown_function('proc_close', $this->handle);
    }


    /**
     * Where does this stream go?
     *
     * @param int $stream
     * @return string
     */
    public function getTarget(int $stream): string
    {
        if (is_resource($this->descriptors[$stream])) {
            return 'resource';
        }

        $target = $this->descriptors[$stream] ?? null;
        if (!is_array($target)) return '';

        if ($target[0] === 'pipe') return 'pipe';
        if ($target[0] === 'file') return $target[1];

        return '';
    }


    /**
     *
     * @param string $data
     * @return int|false
     */
    public function write(string $data)
    {
        return fwrite($this->pipes[0], $data);
    }


    /**
     *
     * @return Generator<string>
     */
    public function read(): \Generator
    {
        $target_out = $this->getTarget(self::STREAM_STDOUT) === 'pipe';
        $target_err = $this->getTarget(self::STREAM_STDERR) === 'pipe';

        if (!$target_out and !$target_err) return;

        $buf_out = '';
        $buf_err = '';

        while (true) {
            // stdout
            if ($target_out) {
                $eof = feof($this->pipes[1]);

                if (!$eof) {
                    $buf_out .= fgets($this->pipes[1], 1024);

                    if (self::eol($buf_out)) {
                        yield substr($buf_out, 0, -1);
                        $buf_out = '';
                    }
                }
            }

            // stderr
            if ($target_err) {
                $eof = feof($this->pipes[2]);

                if (!$eof) {
                    $buf_err .= fgets($this->pipes[2], 1024);

                    if (self::eol($buf_err)) {
                        yield substr($buf_err, 0, -1);
                        $buf_err = '';
                    }
                }
            }

            // End of input!
            if (!isset($eof) or $eof) break;

            // Sleep for a bit.
            usleep(10 * 1000);
        }

        // Flush standard out.
        if ($buf_out) {
            self::eol($buf_out);
            if ($buf_out) yield $buf_out;
        }

        // Flush standard output.
        if ($buf_err) {
            self::eol($buf_err);
            if ($buf_err) yield $buf_err;
        }
    }


    /**
     *
     * @return string
     */
    public function readAll(): string
    {
        $output = '';
        foreach ($this->read() as $line) {
            $output .= $line . PHP_EOL;
        }
        return $output;
    }


    /**
     *
     * @return int
     */
    public function close()
    {
        if ($this->exit !== false) {
            return $this->exit;
        }

        $this->exit = proc_close($this->handle);
        return $this->exit;
    }


    /**
     *
     * @param int $signal
     * @return int
     */
    public function kill($signal = 15)
    {
        if ($this->exit !== false) {
            return $this->exit;
        }

        $status = proc_terminate($this->handle, $signal);

        if (!$status) {
            throw new Exception('Cannot terminate process');
        }

        $this->exit = proc_close($this->handle);
        return $this->exit;
    }


    /**
     * This kind of gross. Don't use this elsewhere, it only makes sense here.
     *
     * This modifies $buffer if it ends with an EOL character.
     * It strips the EOL and returns true. Otherwise false.
     *
     * @param string $buffer
     * @return bool
     */
    private static function eol(string &$buffer): bool
    {
        if (substr_compare($buffer, PHP_EOL, -1, 1) === 0) {
            $buffer = substr($buffer, 0, -1);
            return true;
        }
        else {
            return false;
        }
    }
}
