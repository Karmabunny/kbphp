<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Write PHP arrays to a file.
 *
 * @package karmabunny\kb
 */
class Generate
{

    /** @var string */
    public string $indent = '    ';

    /** @var resource */
    protected mixed $stream;

    /** @var bool */
    protected bool $close;

    /** @var int */
    protected int $depth = 1;


    /**
     *
     * @param string|resource $target
     * @return void
     */
    public function __construct(mixed $target)
    {
        if (is_string($target)) {
            $this->stream = fopen($target, 'w');
            $this->close = true;
        }
        else {
            $this->stream = $target;
            $this->close = false;
        }

        fwrite($this->stream, "<?php\n");
    }


    /**
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->close) {
            @fflush($this->stream);
            @fclose($this->stream);
        }
    }

    /**
     *
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     *
     * @param string $comment
     * @return void
     */
    public function comment(string $comment): void
    {
        fwrite($this->stream, "// {$comment}\n");
    }


    /**
     *
     * @param array $array
     * @return void
     */
    public function write(array $array): void
    {
        fwrite($this->stream, "return [\n");

        foreach ($array as $key => $value) {
            $this->writeItem($key, $value);
        }

        fwrite($this->stream, "];\n");
    }


    /**
     *
     * @param string|int|null $key
     * @param mixed $value
     * @return void
     */
    protected function writeItem(string|int|null $key, mixed $value): void
    {
        if ($key !== null and !is_int($key)) {
            $key = "'{$key}'";
        }

        $indent = str_repeat($this->indent, $this->depth);

        if (is_array($value)) {
            $this->depth += 1;

            if ($key === null) {
                fwrite($this->stream, "{$indent}[\n");
            }
            else {
                fwrite($this->stream, "{$indent}{$key} => [\n");
            }

            $numeric = Arrays::isNumeric($value);

            foreach ($value as $key => $item) {
                $this->writeItem($numeric ? null : $key, $item);
            }

            fwrite($this->stream, "{$indent}],\n");

            $this->depth -= 1;
        }
        else {
            if (is_string($value)) {
                $value = "'" . addcslashes($value, "\\'") . "'";
            }
            else if (is_numeric($value)) {
                $value = (string) $value;
            }
            else if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            else {
                $value = 'null';
            }

            if ($key === null) {
                fwrite($this->stream, "{$indent}{$value},\n");
            }
            else {
                fwrite($this->stream, "{$indent}{$key} => {$value},\n");
            }
        }
    }
}
