<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use Throwable;
use Traversable;

/**
 * A CSV Exporter.
 *
 * @todo Tests!
 * @todo Option for no headers.
 *
 * @package karmabunny\kb
 */
class CsvExport
{
    /** Store blob csvs in memory if under 5mb, otherwise use a temp file. */
    const MAX_MEMORY = 5 * 1024 * 1024;

    /** Identify dirty items. */
    const DIRTY_CHARS = ' "\r\n\t';

    /** @var resource */
    public $handle;

    /** @var array */
    public $formatters = [];

    /** @var string */
    public $break = "\n";

    /** @var string */
    public $delimiter = ',';

    /** @var string */
    public $null = '\N';

    /** @var string */
    public $enclosure = '"';

    /** @var string */
    public $escape = '\\';

    /** @var array|null */
    public $headers = null;

    /** @var string */
    private $dirty_re;

    /** @var bool */
    private $_own_handles = false;

    /**
     * Configure the CSV output format.
     * By default:
     *  - break is a LF \n
     *  - delimiter is a comma ,
     *  - null is \N
     *  - enclosure is "
     *  - escape is \
     *
     * Headers is a map of keys -> values.
     *
     * For example:
     *   $csv = new CsvExport([
     *     'headers' => [ 'val1' => 'Value 1', 'val2' => 'Value 2' ],
     *   ]);
     *   $csv->add([ 'val1' => 'ABC', 'val2' => 'DEF' ]);
     *   $csv->build();
     *
     * Produces:
     *   Value 1,Value 2\n
     *   ABC,DEF\n
     *
     * If headers is null it will produce headers from the first row.
     *
     * @param resource|array|null $handle
     * @param array $config [break, delimiter, null, enclosure, escape, headers]
     * @throws Exception
     */
    public function __construct($handle = null, $config = [])
    {
        // Backwards compat.
        if (is_array($handle)) {
            $config = $handle;
            $handle = null;
        }

        foreach ($config as $key => $val) {
            $this->$key = $val;
        }

        // Null handle means string export.
        if ($handle === null) {
            $this->_own_handles = true;
            $handle = @fopen('php://temp/maxmemory:' . self::MAX_MEMORY, 'r+');

            if ($handle === null) {
                throw new Exception('Failed to open temporary memory.');
            }
        }

        $this->handle = $handle;

        $dirty = [
            $this->break,
            $this->delimiter,
            $this->enclosure,
            $this->escape,
        ];

        $dirty_chars = self::DIRTY_CHARS;
        foreach ($dirty as $char) {
            if (strpos($dirty_chars, $char) === false) {
                $dirty_chars .= $char;
            }
        }

        $this->dirty_re = "/[{$dirty_chars}]/";

        if ($this->headers) {
            $this->_write($this->headers);
        }
    }


    /**
     * Add a row.
     *
     * @param iterable $model
     * @return void
     */
    public function add($model)
    {
        if (!is_array($model)) {
            $model = iterator_to_array($model);
        }

        // Use the first model as the key names.
        // This creates a keyed array: 'attr' => 'attr'.
        if ($this->headers === null) {
            $keys = array_keys($model);
            $this->headers = array_combine($keys, $keys);
            $this->_write($this->headers);
        }

        $this->_write($model);
    }


    /**
     * All all models within a traversable value.
     *
     * If an item in not an array or iterable, it is skipped.
     *
     * @param iterable $models
     * @return void
     */
    public function addAll($models)
    {
        foreach ($models as $model) {
            if (!is_array($model) and !($model instanceof \Traversable)) continue;
            $this->add($model);
        }
    }


    /**
     * Add a formatter for a named attribute.
     *
     * Use this for things like DateTime or arrays. Number types and classes
     * with a '__toString()' should be fine.
     *
     * Or even if you want to format a float, something like this should work:
     * $csv->format('blah', fn($item) => sprintf("%.2f", $item));
     *
     * @param string|string[] $attribute
     * @param callable $cb
     * @return void
     */
    public function format($attribute, callable $cb)
    {
        if (is_string($attribute)) {
            $this->formatters[$attribute] = $cb;
        }
        else {
            foreach ($attribute as $attr) {
                $this->format($attr, $cb);
            }
        }
    }


    /**
     * Internal formatter function.
     *
     * @param string $attribute
     * @return string
     */
    private function _format(string $attribute, $value): string
    {
        // Nulls are gross.
        if ($value === null) {
            return $this->null;
        }

        // Ooh, we have a formatter.
        if (isset($this->formatters[$attribute])) {
            $value = $this->formatters[$attribute]($value);
        }

        // In then end though, we always want a string.
        try {
            $value = @(string) $value;
        }
        catch (Throwable $exception) {
            $value = 'ERR';
        }

        // Let's not break things.
        $value = $this->clean($value);

        return $value;
    }


    /**
     * Format and build the CSV payload all at once.
     *
     * @return string
     */
    public function build(): string
    {
        $ok = rewind($this->handle);
        if (!$ok) throw new Exception('Cannot read handle.');

        $buffer = '';
        while ($chunk = fread($this->handle, 1024)) {
            $buffer .= $chunk;
        }

        if ($this->_own_handles) {
            fclose($this->handle);
        }

        return $buffer;


        // No data!
        if (empty($this->rows)) return '';

        // Use the first model as the key names.
        // This creates a keyed array: 'attr' => 'attr'.
        if ($this->headers === null) {
            $keys = array_keys(Arrays::first($this->rows));
            $headers = array_combine($keys, $keys);
        }

        $csv = [];

        // Write the headers, but clean them first.
        if ($headers) {
            $titles = array_map([$this, 'clean'], array_values($headers));
            $csv[] = implode($this->delimiter, $titles);
        }

        foreach ($this->rows as $row) {
            $items = [];

            // Format/clean the things.
            for ($i = 0; $i < count($headers); $i++) {
                $items[] = $this->_format($headers[$i], $row[$i]);
            }

            // Mush them.
            $csv[] = implode($this->delimiter, $items);
        }

        // Mush them one more time.
        return implode($this->break, $csv);
    }


    /**
     * @param string $dirty
     * @return string cleaned
     */
    public function clean(string $dirty): string
    {
        if (preg_match($this->dirty_re, $dirty)) {
            $dirty = str_replace($this->enclosure, $this->escape . $this->enclosure, $dirty);
            $dirty = $this->enclosure . $dirty . $this->enclosure;
            return $dirty;
        }
        else {
            return $dirty;
        }
    }


    /**
     *
     * @param array $rows
     * @return void
     */
    protected function _write(array $rows)
    {
        $first = true;

        // Only add items that match the header set.
        // If the model is missing a header item - it's null.
        foreach ($this->headers as $key => $name) {
            $value = $rows[$key] ?? null;

            if (!$first) fwrite($this->handle, $this->delimiter);
            fwrite($this->handle, $this->_format($name, $value));
            $first = false;
        }

        fwrite($this->handle, $this->break);
    }
}
