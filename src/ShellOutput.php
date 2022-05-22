<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use Generator;

/**
 * @package karmabunny\kb
 */
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
    public $pid = -1;

    /** @var bool */
    public $running = false;

    /** @var int|false */
    public $exit = false;

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

        $this->isRunning();

        $this->descriptors = $config->getDescriptors();

        if ($this->getTarget(self::STREAM_STDOUT) === 'pipe') {
            stream_set_blocking($pipes[1], false);
        }

        if ($this->getTarget(self::STREAM_STDERR) === 'pipe') {
            stream_set_blocking($pipes[2], false);
        }

        register_shutdown_function($this->shutdown());
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
     * Write data to the standard input.
     *
     * Note, this only works if the stdin descriptor is 'pipe'.
     *
     * @param string $data
     * @return int|false
     * @throws ShellException
     */
    public function write(string $data)
    {
        $target = $this->getTarget(self::STREAM_STDIN);
        if ($target !== 'pipe') {
            throw new ShellException('Standard input is not a pipe');
        }

        return fwrite($this->pipes[0], $data);
    }


    /**
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        $status = @proc_get_status($this->handle);

        if ($status) {
            $this->pid = $status['pid'];
            $this->running = $status['running'];

            if (!$this->running) {
                $this->exit = $status['exitcode'];
            }
        }

        return $this->running;
    }


    /**
     * Read the command output, line by line.
     *
     * The command is executed in non-blocking mode.
     *
     * @param int $stream STREAM enum
     * @param int|null $chunk_size bytes to read at a time
     * @return Generator<string>
     */
    public function read($stream = self::STREAM_ALL, int $chunk_size = null): Generator
    {
        $target_out = (
            ($stream & self::STREAM_STDOUT) and
            $this->getTarget(self::STREAM_STDOUT) === 'pipe'
        );
        $target_err = (
            ($stream & self::STREAM_STDERR) and
            $this->getTarget(self::STREAM_STDERR) === 'pipe'
        );

        if (!$target_out and !$target_err) return;

        if ($chunk_size === null) {
            $chunk_size = $this->config->chunk_size;
        }

        $buf_out = '';
        $buf_err = '';

        while (true) {
            // stdout
            if ($target_out) {
                $eof = feof($this->pipes[1]);

                if (!$eof) {
                    $buf_out .= fgets($this->pipes[1], $chunk_size);

                    if (self::eol($buf_out)) {
                        if ($buf_out) yield $buf_out;
                        $buf_out = '';
                    }
                }
            }

            // stderr
            if ($target_err) {
                $eof = feof($this->pipes[2]);

                if (!$eof) {
                    $buf_err .= fgets($this->pipes[2], $chunk_size);

                    if (self::eol($buf_err)) {
                        if ($buf_err) yield $buf_err;
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
     * @param int $chunk
     * @return null|string
     */
    public function readRaw(int $chunk = 1024)
    {
        $target = $this->getTarget(self::STREAM_STDOUT);
        if ($target !== 'pipe') {
            throw new ShellException('Standard output is not a pipe');
        }

        if(feof($this->pipes[1])) return null;
        return fread($this->pipes[1], $chunk);
    }


    /**
     * Read everything all at once.
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
     * Wait until the process has finished.
     *
     * @return string The last line of output.
     */
    public function wait(): string
    {
        $stream = $this->read();
        foreach ($stream as $line);
        return $line ?? '';
    }


    /**
     * Close the process handle.
     *
     * @return int exit code
     */
    public function close()
    {
        if ($this->exit !== false) {
            return $this->exit;
        }

        $status = @proc_get_status($this->handle);

        if ($status and !$status['running']) {
            $this->running = false;
            $this->exit = $status['exitcode'];
        }
        else {
            $this->exit = @proc_close($this->handle);
            $this->running = false;
        }

        return $this->exit;
    }


    /**
     * Terminate the process and close the handle.
     *
     * @param int $signal
     * @return int exit code
     */
    public function kill($signal = SIGTERM)
    {
        if ($this->exit !== false) {
            return $this->exit;
        }

        $status = proc_terminate($this->handle, $signal);

        if (!$status) {
            throw new ShellException('Cannot terminate process');
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


    /**
     * @return callable
     */
    private function shutdown()
    {
        return function() {
            if ($this->isRunning()) return;
            $this->close();
        };
    }
}
