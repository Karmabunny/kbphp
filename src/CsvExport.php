<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

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

    /** Identify dirty items. */
    private const DIRTY_RE = "/[ ,'\"\\r\\n]/";

    /** Clean these out. */
    private const BREAK_RE = "/[\\r\\n]+/";

    /** @var array */
    public $rows = [];

    /** @var array */
    public $formatters = [];

    /** @var string */
    public $break = "\n";

    /** @var string */
    public $delimiter = ",";

    /** @var string */
    public $null = "-";

    /** @var array|null */
    public $headers = null;

    /**
     *
     * Configure the CSV output format.
     * By default:
     *  - break is a LF \n
     *  - delimiter is a comma ,
     *  - null is a hyphen -
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
     * @param array $config [break, delimiter, null, headers]
     */
    public function __construct($config = [])
    {
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
    }


    /**
     * Add a row.
     *
     * @param array|Traversable $model
     * @return void
     */
    public function add($model)
    {
        // Use the first model as the key names.
        // This creates a keyed array: 'attr' => 'attr'.
        if ($this->headers === null) {
            $keys = array_keys($model->attributes);
            $this->headers = array_combine($keys, $keys);
        }

        // Only add items that match the header set.
        $this->rows[] = array_map(function($attribute) use ($model) {
            return $model->attributes[$attribute];
        }, array_keys($this->headers));
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
    public function format(string|array $attribute, callable $cb)
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
     * @return mixed
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
            $value = (string) $value;
        }
        catch (\Exception $exception) {
            error_log('Could not format CSV item: ' . $attribute);
            $value = 'ERR';
        }

        // Let's not break things.
        $value = self::clean($value);

        return $value;
    }


    /**
     * Format and build the CSV payload all at once.
     *
     * @return string
     */
    public function build(): string
    {
        $csv = [];

        // Mush the headers and clean them.
        if ($this->headers)
        $csv[] = implode($this->delimiter,
            array_map(function($item) {
                return self::clean($item);
            },
            array_values($this->headers)
        ));

        $headers = array_keys($this->headers);

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
     * Basically, CSVs don't like spaces or newlines or quotes.
     *
     * @param string $dirty
     * @return string cleaned
     */
    public static function clean(string $dirty): string
    {
        if (preg_match(self::DIRTY_RE, $dirty)) {
            $dirty = preg_replace(self::BREAK_RE, '', $dirty);
            $dirty = str_replace("\"", "\\\"", $dirty);
            $dirty = trim($dirty);
            $dirty = '"' . $dirty . '"';
            return $dirty;
        }
        else {
            return trim($dirty);
        }
    }
}
