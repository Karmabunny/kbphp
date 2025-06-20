<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use karmabunny\kb\Arrays;


/**
 *
 * @package karmabunny\kb
 */
class Text {

    /**
     * Set everything to lowercase.
     */
    const NORMALIZE_CASE = 0x1;

    /**
     * Normalize similar looking characters.
     * (good for OCR texts).
     */
    const NORMALIZE_ALPHA = 0x2;

    /**
     * Convert multi-byte to random single-byte characters.
     */
    const NORMALIZE_MULTIBYTE = 0x4;

    /**
     * Normalize all the things.
     */
    const NORMALIZE_ALL = 0x1 | 0x2 | 0x4;

    /**
     * Perform finds only against the needle length.
     */
    const FIND_STARTS_WITH = 0x1000;


    /**
     * Mask preset types
     */
    const MASK_TYPE_FIRSTNAME = 'FIRSTNAME';
    const MASK_TYPE_LASTNAME = 'LASTNAME';
    const MASK_TYPE_FIRSTLASTNAME = 'FIRSTLASTNAME';
    const MASK_TYPE_EMAIL = 'EMAIL';
    const MASK_TYPE_PHONE = 'PHONE';
    const MASK_TYPE_SPACES = 'SPACES';


    /** @var string */
    static $ENCODING = 'UTF-8';

    /** @var int */
    static $MIN_SIMILAR_PERCENT = 85;

    /** @var int */
    static $DISTANCE_FACTOR = 10;

    /** @var array */
    static $ALPHA_RULES = [
        "a4 e3 o0D ilL1!| `’",
        "AA EE OOO IIIIII ''",
    ];


    /** @var array|null Not for public use. */
    static $_map = null;


    /**
     * Normalize a string with a set of rules.
     *
     * Flags:
     * - NORMALIZE_CASE
     * - NORMALIZE_ALPHA
     * - NORMALIZE_MULTIBYTE
     *
     * Note the output of this function isn't useful for much else but the
     * similarity/compare functions.
     *
     * @param string $str
     * @param int $flags
     * @return string
     */
    public static function normalize(string $str, $flags = self::NORMALIZE_ALL)
    {
        if ($flags & self::NORMALIZE_ALPHA) {
            $str = self::normalizeAlpha($str);
        }

        if ($flags & self::NORMALIZE_CASE) {
            if (function_exists('mb_convert_case')) {
                $str = mb_strtolower($str, self::$ENCODING);
            }
            else {
                $str = strtolower($str);
            }
        }

        if ($flags & self::NORMALIZE_MULTIBYTE) {
            $map = self::$_map ?? [];
            $str = self::normalizeMultibyte($str, $map);
        }

        return $str;
    }


    /**
     * Normalize similar looking characters.
     *
     * @param string $str
     * @return string
     */
    public static function normalizeAlpha(string $str)
    {
        list($find, $replace) = self::$ALPHA_RULES;
        return strtr($str, $find, $replace);
    }


    /**
     * Replaces multi-byte characters with random single-byte UTF-8 characters.
     *
     * This resolves issues with levenshtein distances being artificially
     * larger than intended.
     *
     * The results are hardly useful or even readable on their own. Replacement
     * characters are not representative of the originals but produce more
     * accurate results for both levenshtein and similar_text.
     *
     * Note this can only handle up to 128 unique multi-byte characters until
     * it deteriorates to the original behaviour.
     *
     * https://www.php.net/manual/en/function.levenshtein.php#113702
     *
     * @param string $str
     * @param array $map
     * @return string
     */
    public static function normalizeMultibyte(string $str, array &$map = [])
    {
        $matches = [];

        // Find all multi-byte characters (cf. utf-8 encoding specs).
        if (!preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches)) {
            // No match - plain ascii string.
            return $str;
        }

        // Update the encoding map with the characters not already met.
        foreach ($matches[0] as $char) {
            if (isset($map[$char])) continue;
            $map[$char] = chr(128 + count($map));
        }

        // Do the remap.
        return strtr($str, $map);
    }


    /**
     * Get the similarity between two strings.
     *
     * @param string $str1
     * @param string $str2
     * @param int $flags one of the normalisation flags
     * @return float 0-100 bigger is better
     */
    public static function similarity(string $str1, string $str2, $flags = self::NORMALIZE_ALL)
    {
        if ($flags) {
            self::$_map = [];
            $str1 = self::normalize($str1, $flags);
            $str2 = self::normalize($str2, $flags);
            self::$_map = null;
        }

        $percent = 0;
        similar_text($str1, $str2, $percent);
        return $percent;
    }


    /**
     * Compare the similarity of two strings using similarity and Levenshtein checks
     *
     * @param string $str1 The first string for comparison
     * @param string $str2 The string to compare the first with
     * @param int $flags one of the normalisation flags
     * @return int lower is better (or -1 if too long)
     */
    public static function compare(string $str1, string $str2, $flags = self::NORMALIZE_ALL)
    {
        if ($flags) {
            self::$_map = [];
            $str1 = self::normalize($str1, $flags);
            $str2 = self::normalize($str2, $flags);
            self::$_map = null;
        }

        // This function should do the -1 response automatically
        // But it appears to be throwing an exception instead
        if (strlen($str1) > 255 or strlen($str2) > 255) {
            return -1;
        }

        return levenshtein($str1, $str2);
    }


    /**
     *
     * @param string $str1
     * @param string $str2
     * @param int $flags one of the normalisation flags
     * @return bool
     */
    public static function similar(string $str1, string $str2, $flags = self::NORMALIZE_ALL)
    {
        if ($flags) {
            self::$_map = [];
            $str1 = self::normalize($str1, $flags);
            $str2 = self::normalize($str2, $flags);
            self::$_map = null;
        }

        $distance = self::compare($str1, $str2, 0);

        // On error - it's not similar.
        if ($distance < 0) {
            return false;
        }

        $len = (strlen($str1) + strlen($str2)) / 2;
        $max_lev = ceil($len / self::$DISTANCE_FACTOR);

        // Valid levenshtein!
        if ($distance < $max_lev) {
            return true;
        }

        $percent = self::similarity($str1, $str2, 0);

        // Valid similarity!
        if ($percent >= self::$MIN_SIMILAR_PERCENT) {
            return true;
        }

        // Not similar enough.
        return false;
    }


    /**
     * Find something similar from a list of options.
     *
     * Great for a 'did you mean?' type of thing.
     *
     * @param string $needle a fuzzy string
     * @param string[] $haystack set of possible options
     * @param int $max limit results
     * @param int $flags one of the normalisation flags
     * @return string[] subset of 'options' in order of closeness
     */
    public static function find(string $needle, array $haystack, $max = 5, $flags = self::NORMALIZE_ALL): array
    {
        self::$_map = [];

        $needle_length = strlen($needle);
        $norm_needle = self::normalize($needle, $flags);
        $results = [];

        foreach ($haystack as $item) {
            // Exact match! Dump everything else!
            if ($needle === $item) {
                $results = [];
                $results[0] = $item;
                break;
            }

            $norm_item = self::normalize($item, $flags);

            if ($flags & self::FIND_STARTS_WITH) {
                $norm_item = substr($norm_item, 0, $needle_length);
            }

            $distance = self::compare($norm_needle, $norm_item, 0);

            // Skip on error.
            if ($distance < 0) continue;

            // Check for big-ness.
            $len = ($needle_length + strlen($item)) / 2;
            $max_lev = ceil($len / self::$DISTANCE_FACTOR);
            if ($distance > $max_lev) continue;

            // Prevent overrides.
            while (isset($results[$distance])) {
                $distance++;
            }

            // Store it.
            $results[$distance] = $item;
        }

        self::$_map = null;

        // Trim first for a faster sort.
        if ($max) {
            $results = array_slice($results, 0, $max, true);
        }

        ksort($results, SORT_NUMERIC);

        // Strip keys.
        $results = array_values($results);

        return $results;
    }


    /**
     *
     * @param string $needle
     * @param array $haystack
     * @param int $max
     * @param int $flags
     * @return array
     */
    public static function startsWith(string $needle, array $haystack, $max = 5, $flags = self::NORMALIZE_ALL): array
    {
        $flags |= self::FIND_STARTS_WITH;
        return self::find($needle, $haystack, $max, $flags);
    }


    /**
     * Returns given string masked by given character, multi-byte safe
     *
     * @param string $word The string to mask, Eg `Apple`
     * @param string $mask_char Optional masking character. Default of `*`
     * @param int $start Optional number of characters to keep from the start of string. Eg `1` = `A****`
     * @param int $end Optional number of characters to keep from the end of string. Eg `2` = `***le`
     * @return string Eg `*****`|`A****`|`****e`|`A***e`
     */
    public static function mask(string $word, $mask_char = '*', $start = 0, $end = 0)
    {
        if (empty($word)) return '';
        if (empty($mask_char)) $mask_char = '*';
        $start = (int) $start;
        $end = (int) $end;

        if ($start < 0) $start = 0;
        if ($end < 0) $end = 0;

        $repeat = mb_strlen($word) - ($start + $end);

        // Prevent errors from leaking the original word
        if ($repeat <= 0)
        {
            $repeat = mb_strlen($word);
            $start = 0;
            $end = 0;
        }

        $replacement = str_repeat($mask_char, $repeat);
        $prefix = mb_substr($word, 0, $start, 'UTF-8');
        $postfix = $end > 0 ? mb_substr($word, mb_strlen($word) - $end, mb_strlen($word), 'UTF-8') : '';

        return "{$prefix}{$replacement}{$postfix}";
    }


    /**
     * Applies preset mask to given string @see `Text::mask`
     *
     * @param string $word
     * @param string $preset Text::MASK_TYPE_*
     * @param string|null $mask_char Optional masking character. Default of `*`
     * @return string
     * @throws Exception Unknown preset
     */
    public static function maskPreset(string $word, string $preset, $mask_char = '*')
    {
        switch ($preset)
        {
            // Keep first character and spaces. `F**** ****`
            case self::MASK_TYPE_FIRSTNAME:
                $parts = explode(' ', $word);

                $func = function($str, $index) use ($mask_char)
                {
                    if ($index == 0) return Text::mask($str, $mask_char, 1, 0);
                    return Text::mask($str, $mask_char);
                };

                $strings = array_map($func, $parts, array_keys($parts));
                return implode(' ', $strings);

            // Keep last character and spaces. `**** ***e`
            case self::MASK_TYPE_LASTNAME:
                $parts = explode(' ', $word);
                $last = Arrays::lastKey($parts);

                $func = function($str, $index) use ($mask_char, $last)
                {
                    if ($index == $last) return Text::mask($str, $mask_char, 0, 1);
                    return Text::mask($str, $mask_char);
                };

                $strings = array_map($func, $parts, array_keys($parts));
                return implode(' ', $strings);

            // Keep first and last character, and spaces. F*** **** ***e
            case self::MASK_TYPE_FIRSTLASTNAME:
                $parts = explode(' ', $word);
                $last = Arrays::lastKey($parts);

                $func = function($str, $index) use ($mask_char, $last)
                {
                    if ($index == 0) return Text::mask($str, $mask_char, 1, 0);
                    if ($index == $last) return Text::mask($str, $mask_char, 0, 1);
                    return Text::mask($str, $mask_char);
                };

                $strings = array_map($func, $parts, array_keys($parts));
                return implode(' ', $strings);

            // Keep first, last, and @ character of prefix and domain. `e***l@d********m`
            case self::MASK_TYPE_EMAIL:
                // Safely handle non email addresses
                if (strpos($word, '@') === false) return self::mask($word);

                list($address, $domain) = explode('@', $word);
                return sprintf(
                    '%s@%s',
                    Text::mask($address, $mask_char, 1, 1),
                    Text::mask($domain, $mask_char, 1, 1)
                );

            // Keep last 3 characters. `*******123`
            case self::MASK_TYPE_PHONE:
                return Text::mask($word, $mask_char, 0, 3);

            // Keep only spaces. `**** ** *****`
            case self::MASK_TYPE_SPACES:
                $parts = explode(' ', $word);

                $func = function($str) use ($mask_char)
                {
                    return Text::mask($str, $mask_char);
                };

                $strings = array_map($func, $parts);
                return implode(' ', $strings);

            default:
                throw new Exception('Unknown MASK_TYPE');
        }
    }
}
