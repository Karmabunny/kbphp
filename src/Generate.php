<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Write PHP arrays to a file.
 *
 * @package Bloom\Base
 */
class Generate
{

    /** @var string */
    public $indent = '    ';

    /** @var resource */
    protected $stream;

    /** @var bool */
    protected $close;

    /** @var int */
    protected $depth = 1;


    /**
     *
     * @param string|resource $target
     * @return void
     */
    public function __construct($target)
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
    public function close()
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
    public function comment(string $comment)
    {
        fwrite($this->stream, "// {$comment}\n");
    }


    /**
     *
     * @param array $array
     * @return void
     */
    public function write(array $array)
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
    protected function writeItem($key, $value)
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
