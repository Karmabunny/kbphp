<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use IteratorAggregate;
use Traversable;

/**
 * A CSV Importer.
 *
 * @todo Tests!
 * @todo Option for no headers.
 *
 * @package karmabunny\kb
 */
class CsvImport implements IteratorAggregate
{

    /** Store blob csvs in memory if under 5mb, otherwise use a temp file. */
    const MAX_MEMORY = 5 * 1024 * 1024;

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

    /** @var bool This importer manages it's own handles. */
    private $_own_handles = false;

    /** @var string */
    private $_escape_re;

    /** @var resource|null */
    private $handle;

    /**
     * Configure the CSV output format.
     * By default:
     *  - break is a LF \n
     *  - delimiter is a comma ,
     *  - null is a \N
     *  - enclosure is "
     *  - escape is \
     *
     * If headers is null it will produce headers from the first row.
     *
     * @param resource $handle a file resource
     * @param array $config [break, delimiter, null, enclosure, escape, headers]
     */
    public function __construct($handle, $config = [])
    {
        $this->handle = $handle;

        foreach ($config as $key => $val) {
            $this->$key = $val;
        }

        $escape = preg_quote($this->escape);
        $this->_escape_re = "/{$escape}(.)/";
    }


    /**
     * Create a new importer from a file path.
     *
     * @param string $filename
     * @param array $config
     * @return null|CsvImport
     */
    public static function fromFile(string $filename, $config = [])
    {
        $handle = @fopen($filename, 'r');
        if ($handle === false) return null;

        $importer = new CsvImport($handle, $config);
        $importer->_own_handles = true;
        return $importer;
    }


    /**
     * Create new import from a csv blob string.
     *
     * @param string $csv
     * @param array $config
     * @return null|CsvImport
     */
    public static function fromString(string $csv, $config = [])
    {
        $handle = @fopen('php://temp/maxmemory:' . self::MAX_MEMORY, 'r+');
        if ($handle === false) return null;

        fputs($handle, $csv);
        rewind($handle);

        $importer = new CsvImport($handle, $config);
        $importer->_own_handles = true;
        return $importer;
    }


    /**
     * Get the CSV headers.
     *
     * Beware, this will consume the first row if headers are not explicitly set.
     *
     * @return string[]
     */
    public function getHeaders(): array
    {
        if ($this->headers === null) {
            $headers = $this->_getcsv();

            // null is EOF.
            if (!$headers) {
                $this->headers = [];
                return [];
            }

            // Trim them.
            // I honestly can't imagine where not trimming a good idea.
            // If you argue otherwise I will fight you.
            $this->headers = array_map('trim', $headers);
        }

        return $this->headers;
    }


    /**
     * Get the next line.
     *
     * @return null|array An associated array or null if EOF.
     */
    public function getLine()
    {
        // Load in headers from the first row.
        $this->getHeaders();

        // null is EOF.
        $row = $this->_getcsv();
        if (!$row) return null;

        $out = [];
        foreach ($this->headers as $index => $key) {
            // Uh, maybe the row has too little values - just fill them.
            $out[$key] = $row[$index] ?? null;

            // Interpret null rows.
            if ($out[$key] === $this->null) {
                $out[$key] = null;
            }

            $out[$key] = preg_replace($this->_escape_re, '$1', $out[$key]);
        }
        return $out;
    }


    /** @inheritdoc */
    public function getIterator(): Traversable
    {
        while ($line = $this->getLine()) yield $line;
    }


    /**
     * Internal wrapper to normalise fgetscsv() behaviour.
     *
     * @return null|array row values or null if EOF.
     */
    private function _getcsv()
    {
        if ($this->handle === null) return null;

        $line = fgetcsv(
            $this->handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if (!$line) {
            if ($this->_own_handles) {
                @fclose($this->handle);
            }
            $this->handle = null;
            return null;
        }

        return $line;
    }
}
