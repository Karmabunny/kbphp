<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace karmabunny\kb;

use karmabunny\interfaces\InflectorInterface;

/**
 * Language inflection such as pluralisation.
 *
 * Includes utilities for converting between snake/camel/kebab string formats.
 *
 * DO NOT use this for i18n. This is strictly for inflection of
 * _INTERNAL CODE UTILITIES_.
 *
 * @package karmabunny\kb
 */
class Inflector extends DataObject implements InflectorInterface
{

    // Cached inflections
    protected $_cache = [];

    // Uncountable and irregular words
    public $uncountable = [];
    public $irregular = [];


    /** @inheritdoc */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = require __DIR__ . '/config/inflector.php';
        }

        parent::__construct($config);
    }


    /** @inheritdoc */
    public function update($config): void
    {
        parent::update($config);
        $this->uncountable = array_combine($this->uncountable, $this->uncountable);
    }


    /**
     * Checks if a word is defined as uncountable.
     *
     * @param   string $word
     * @return  bool
     */
    public function uncountable(string $word): bool
    {
        $word = strtolower(trim($word));
        return array_key_exists($word, $this->uncountable);
    }


    /**
     * Makes a plural word singular.
     *
     * @param   string   $word
     * @param   int      $count number of things
     * @return  string
     */
    public function singular($word, $count = 1): string
    {
        $key = "singular_{$word}_{$count}";

        // Exists in the cache.
        if (array_key_exists($key, $this->_cache)) {
            return $this->_cache[$key];
        }

        $word = $this->_singular($word, $count);

        $this->_cache[$key] = $word;
        return $word;
    }


    /**
     * Makes a singular word plural.
     *
     * @param   string   $word
     * @param   int      $count
     * @return  string
     */
    public function plural($word, $count = 0): string
    {
        $key = "singular_{$word}_{$count}";

        // Exists in the cache.
        if (array_key_exists($key, $this->_cache)) {
            return $this->_cache[$key];
        }

        $word = $this->_plural($word, $count);

        $this->_cache[$key] = $word;
        return $word;
    }


    /**
     * Makes a plural word singular.
     *
     * @param   string   $word
     * @param   int      $count number of things
     * @return  string
     */
    protected function _singular($word, $count = 1)
    {
        // Remove garbage
        $word = strtolower(trim($word));
        $count = (int) $count;

        // Do nothing with a single count
        if ($count === 0 OR $count > 1) {
            return $word;
        }

        // Not countable.
        if ($this->uncountable($word)) {
            return $word;
        }

        // It's irregular.
        if ($irregular = array_search($word, $this->irregular)) {
            return $irregular;
        }

        // Remove "es"
        if (preg_match('/[sxz]es$/', $word) OR preg_match('/[^aeioudgkprt]hes$/', $word)) {
            return substr($word, 0, -2);
        }

        // Swap "ies" with "y".
        if (preg_match('/[^aeiou]ies$/', $word)) {
            return substr($word, 0, -3).'y';
        }

        // Strip "s", but not "ss".
        if (substr($word, -1) === 's' AND substr($word, -2) !== 'ss') {
            return substr($word, 0, -1);
        }

        return $word;
    }


    /**
     * Makes a singular word plural.
     *
     * @param   string   $word
     * @param   int      $count
     * @return  string
     */
    protected function _plural($word, $count = 0)
    {
        // Remove garbage
        $word = strtolower(trim($word));
        $count = (int) $count;

        // Do nothing with singular
        if ($count === 1) {
            return $word;
        }

        // Not countable.
        if ($this->uncountable($word)) {
            return $word;
        }

        // It's irregular.
        if ($irregular = $this->irregular[$word] ?? null) {
            return $irregular;
        }

        // Add an "es" suffix.
        if (preg_match('/[sxz]$/', $word) OR preg_match('/[^aeioudgkprt]h$/', $word)) {
            return $word . 'es';
        }

        // Change "y" to "ies".
        if (preg_match('/[^aeiou]y$/', $word)) {
            return substr_replace($word, 'ies', -1);
        }

        return $word . 's';
    }


    /**
     * Makes a phrase camel case.
     *
     * @param   string $phrase
     * @param   bool $first Upper case the first letter
     * @return  string
     */
    public static function camelize($phrase, $first = true)
    {
        $phrase = self::humanize($phrase);
        $phrase = ucwords(preg_replace('/\s+/', ' ', $phrase));
        $phrase = str_replace(' ', '', $phrase);

        if (!$first) {
            $phrase = lcfirst($phrase);
        }

        return $phrase;
    }


    /**
     * Makes a phrase underscored.
     *
     * @param   string $phrase
     * @return  string
     */
    public static function underscore($phrase)
    {
        $phrase = self::humanize($phrase);
        return preg_replace('/\s+/', '_', trim($phrase));
    }


    /**
     * Makes a phrase kebab-case.
     *
     * @param   string $phrase
     * @return  string
     */
    public static function kebab($phrase)
    {
        $phrase = self::humanize($phrase);
        return preg_replace('/\s+/', '-', trim($phrase));
    }


    /**
     * Makes a phrase human-readable from any form (kebab, underscore, camel).
     *
     * @param   string $phrase
     * @return  string
     */
    public static function humanize($phrase)
    {
        // Convert from underscore + kebab.
        $phrase = trim(preg_replace('/[\s_-]+/', ' ', $phrase));

        // Convert from CamelCase.
        $phrase = preg_replace_callback('/(^|[a-z])([A-Z])/', function($matches) {
            return "{$matches[1]} {$matches[2]}";
        },
        $phrase);

        // Tidy up.
        return strtolower(trim($phrase));
    }
}
