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
    public $cmd;

    /** @var array */
    public $args = [];

    /** @var string|null */
    public $cwd = null;

    /** @var string[] */
    public $env = [];

    /** @var string|array|resource */
    public $stdin = 'pipe';

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
            0 => self::parseDescriptor('r', $this->stdout),
            1 => self::parseDescriptor('w', $this->stdout),
            2 => self::parseDescriptor('w', $this->stderr),
        ];
    }


    /**
     *
     * @return string
     */
    public function getCommand(): string
    {
        return Shell::escape($this->cmd, $this->args);
    }


    /**
     *
     * @param string $type
     * @param mixed $descriptor
     * @return resource|array
     */
    private static function parseDescriptor(string $type, $descriptor)
    {
        // Resource, cool.
        if (is_resource($descriptor)) {
            return $descriptor;
        }

        // Custom pipe config, cool.
        if (is_array($descriptor)) {
            return $descriptor;
        }

        // Shorthand pipe, ok.
        if ($descriptor === 'pipe') {
            return ['pipe', $type];
        }

        // String is a filename.
        if (is_string($descriptor)) {
            $pipe = ['file', $descriptor];

            // Out type will 'append' by default. Dunno about that.
            if ($type == 'w') $pipe[] = 'a';
            return $pipe;
        }

        // Dunno, just use the default.
        return ['pipe', $type];
    }

}
