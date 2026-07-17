<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

/**
 * @package karmabunny\kb
 */
class ShellOptions extends Collection
{
    /** @var string required */
    public string $cmd = '';

    /** @var array */
    public array $args = [];

    /** @var string|null */
    public ?string $cwd = null;

    /** @var array<string,string> */
    public array $env = [];

    /** @var string|array|resource|false */
    public mixed $stdin = 'pipe';

    /** @var string|array|resource|false */
    public mixed $stdout = 'pipe';

    /** @var string|array|resource|false */
    public mixed $stderr = 'pipe';

    /** @var int Limit for fgets() in bytes. */
    public int $chunk_size = 1024;


    /**
     * Parse options.
     *
     * This accepts a config or string.
     *
     * Given a string it creates a command with default options.
     *
     * @param string|array|ShellOptions $config
     * @return ShellOptions
     */
    public static function parse(string|array|ShellOptions $config): self
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
     * Get file descriptors.
     *
     * @return array resource|array
     */
    public function getDescriptors(): array
    {
        $descriptors = [];

        if ($this->stdin) {
            $descriptors[0] = self::parseDescriptor('r', $this->stdin);
        }
        if ($this->stdout) {
            $descriptors[1] = self::parseDescriptor('w', $this->stdout);
        }
        if ($this->stderr) {
            $descriptors[2] = self::parseDescriptor('w', $this->stderr);
        }

        return $descriptors;
    }


    /**
     * Get a safe command string with the arguments correctly interpolated.
     *
     * @return string
     */
    public function getCommand(): string
    {
        return Shell::escape($this->cmd, $this->args);
    }


    /**
     * Normalise a descriptor into something that can be passed to proc_open().
     *
     * A descriptor is either a resource or a pipe config, but we also have
     * shorthand for 'pipe' and 'file' descriptors. For whatever reason.
     *
     * Shorthands are:
     * - `'pipe' => [ 'pipe', mode ]`
     * - `'/path/to/file' => [ 'file', '/path/to/file', mode ]`
     *
     * @param string $mode 'r' or 'w'
     * @param resource|array|string $descriptor
     * @return resource|array
     */
    private static function parseDescriptor(string $mode, mixed $descriptor): mixed
    {
        // Resource, cool.
        if (is_resource($descriptor)) {
            return $descriptor;
        }

        // Custom pipe config, cool.
        // (Assuming it's valid)
        if (is_array($descriptor)) {
            return $descriptor;
        }

        // Shorthand pipe, ok.
        if ($descriptor === 'pipe') {
            return ['pipe', $mode];
        }

        // String is a filename.
        if (is_string($descriptor)) {
            $pipe = ['file', $descriptor];

            // Out type will 'append' by default. Dunno about that.
            if ($mode == 'w') {
                $pipe[] = 'a';
            }

            return $pipe;
        }

        // Dunno, just use the default.
        return ['pipe', $mode];
    }

}
