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
