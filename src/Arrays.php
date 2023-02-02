<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Generator;
use Throwable;
use Traversable;

/**
 * Array utilities.
 *
 * @package karmabunny\kb
 */
class Arrays
{

    /**
     * Get the first key + value of an iterable.
     *
     * @param iterable $iterable
     * @return array [ key, item ]
     */
    public static function firstPair($iterable)
    {
        foreach ($iterable as $key => $item) {
            return [$key, $item];
        }

        return [null, null];
    }


    /**
     * Get the last key + value of an iterable.
     *
     * @param iterable $iterable
     * @return array [ key, item ]
     */
    public static function lastPair($iterable)
    {
        if (is_array($iterable)) {
            $item = end($iterable);
            $key = key($iterable);
        }
        else {
            foreach ($iterable as $key => $item);
        }

        return [
            $key ?? null,
            $item ?? null,
        ];
    }


    /**
     * Get the first key of an iterable.
     *
     * @param iterable $iterable
     * @return int|string|null
     */
    public static function firstKey($iterable)
    {
        list($key) = self::firstPair($iterable);
        return $key;
    }


    /**
     * Get the last key of an iterable.
     *
     * @param iterable $iterable
     * @return int|string|null
     */
    public static function lastKey($iterable)
    {
        list($key) = self::lastPair($iterable);
        return $key;
    }


    /**
     * First item in the iterable.
     *
     * @param iterable $iterable
     * @return mixed
     */
    public static function first($iterable)
    {
        list( , $value) = self::firstPair($iterable);
        return $value;
    }


    /**
     * Last item in the iterable.
     *
     * @param iterable $iterable
     * @return mixed
     */
    public static function last($iterable)
    {
        list( , $value) = self::lastPair($iterable);
        return $value;
    }


    /**
     * Create a reversed iterator of an array/traversable.
     *
     * Iterators will likely need implement SeekableIterator.
     *
     * Useful for _big_ arrays because `array_reverse()` creates a copy,
     * whereas this one does not. *Note: memory consumption is completely
     * untested and unfounded.*
     *
     * TBH not entirely sure why I wrote this.
     *
     * @param iterable $array
     * @return Generator
     */
    public static function reverse($array)
    {
        end($array);
        while (($key = key($array)) !== null)
        {
            yield $key => current($array);
            prev($array);
        }
    }


    /**
     * Fill an array with the results of a callable.
     *
     * Each index is provided as an argument.
     *
     * The `$index` arg is a reference and can be modified to alter the key.
     *
     * For a more straight-forward API, see `filledKeys()`.
     *
     * ```
     * Arrays::fill(5, function(&$index) {
     *     $index = 4 - $index;
     *     return $index;
     * });
     * // => [4 => 0, 3 => 1, 2 => 2, 1 => 3, 0 => 4]
     * ```
     *
     * @param int $size
     * @param callable $fn (&$index) => $value
     * @return array
     */
    public static function fill(int $size, callable $fn)
    {
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            $key = $i;
            $value = $fn($key);
            $array[$key] = $value;
        }
        return $array;
    }


    /**
     * Fill a keyed array with the results of a callable.
     *
     * Each index is provided as an argument.
     * The callable is expected to return an array pair [key, value].
     *
     * ```
     * Arrays::fillKeyed(5, fn($index) => [4 - $index, $index]);
     * // => [4 => 0, 3 => 1, 2 => 2, 1 => 3, 0 => 4]
     * ```
     *
     * @param int $size
     * @param callable $fn ($index) => [$key, $value]
     * @return array
     */
    public static function fillKeyed(int $size, callable $fn)
    {
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            list($key, $value) = $fn($i);
            $array[$key] = $value;
        }
        return $array;
    }


    /**
     * Produce a keyed set of 'keys' with values from 'array' otherwise filled
     * with the 'fill' value.
     *
     * Extraneous keys within the 'array' are ignored.
     *
     * For example:
     * ```
     * $output = fillIntersectionKeys(['a', 'b'], ['b' => 1, 'c' => 2], 0);
     * // => ['a' => 0, 'b' => 1]
     * ```
     *
     * @param array $keys
     * @param array $array
     * @param mixed $fill
     * @return array
     */
    public static function fillIntersectionKeys(array $keys, array $array, $fill = null)
    {
        $keys = array_fill_keys($keys, $fill);
        $array = array_merge($keys, array_intersect_key($array, $keys));
        return $array;
    }


    /**
     * Join together both key and values of an array.
     *
     * This has _two_ glues. One for between the item (outer glue) and one for
     * between the key and value (inner glue).
     *
     * @param array $array
     * @param string $outer_glue
     * @param string $inner_glue
     * @return string
     */
    public static function implodeWithKeys(array $array, string $outer_glue = '', string $inner_glue = '')
    {
        $output = '';

        foreach ($array as $key => $value) {
            $output .= $outer_glue . $key . $inner_glue . $value;
        }

        return substr($output, strlen($outer_glue));
    }


    /**
     * Find a matching item.
     *
     * The callable is provided with the value FIRST and the key SECOND.
     *
     * ```
     * // You can write:
     * Arrays::find($stuff, fn($item) => $item->id === 100);
     *
     * // Or maybe:
     * Arrays::find($stuff, fn($item, key) => $key === 12 and $item->name === 12);
     * ```
     *
     * @param iterable $iterable
     * @param callable $fn ($value, $key) => bool
     * @return mixed|null
     */
    public static function find($iterable, callable $fn)
    {
        foreach ($iterable as $key => $item) {
            if ($fn($item, $key)) return $item;
        }

        return null;
    }


    /**
     * Array reduce - with keys.
     *
     * This literally identical to `array_reduce` except it also includes
     * the key value as 3rd argument to the callable.
     *
     * Like this:
     * ```
     * Arrays::reduce($list, fn($sum, $item, $key) => $sum + $key, 0);
     * ```
     *
     * @param iterable $iterable
     * @param callable $fn (sum, item, key) => array
     * @param mixed|null $initial
     * @return mixed
     */
    public static function reduce($iterable, callable $fn, $initial = null)
    {
        $carry = $initial;
        foreach ($iterable as $key => $value) {
            $carry = $fn($carry, $value, $key);
        }
        return $carry;
    }


    /**
     * Reduce the array to a subset, as defined by the keys parameter.
     *
     * @param array $array
     * @param string[] $keys
     * @param bool $fill Replace missing keys with null.
     * @return array
     */
    public static function filterKeys(array $array, array $keys, $fill = false): array
    {
        $items = [];

        foreach ($keys as $key) {
            if (!$fill and !array_key_exists($key, $array)) continue;
            $items[$key] = $array[$key] ?? null;
        }

        return $items;
    }


    /**
     * Like `array_map` but includes a 'key' argument.
     *
     * ```
     * Arrays::mapKeys($list, fn($item, $key) => [$key, $item]);
     * ```
     *
     * No, this cannot perform a zip.
     *
     * @param array $array
     * @param callable $fn (item, key) => [key, item]
     * @return array
     */
    public static function mapKeys(array $array, $fn): array
    {
        $items = [];

        foreach ($array as $key => $item) {
            list($key, $item) = $fn($item, $key);
            $items[$key] = $item;
        }

        return $items;
    }


    /**
     * Shuffle an array, optionally preserving the keys.
     *
     * Yes, this is just the native shuffle() but it's also non-destructive
     * with key preserving options.
     *
     * @param iterable $array
     * @param bool $preserve_keys
     * @return array
     */
    public static function shuffle($array, bool $preserve_keys = false): array
    {
        if (!is_array($array)) {
            $array = iterator_to_array($array, $preserve_keys);
        }

        if ($preserve_keys) {
            $keys = array_keys($array);
            shuffle($keys);

            $new = [];
            foreach ($keys as $key) {
                $new[$key] = $array[$key];
            }

            return $new;
        }
        else {
            shuffle($array);
            return $array;
        }
    }


    /**
     * Flat arrays, with optional key support.
     *
     * @param array $array
     * @param bool $keys
     * @return array
     */
    public static function flatten(array $array, $keys = false): array
    {
        $return = [];

        if ($keys) {
            $fn = function($item, $key) use (&$return) {
                $return[$key] = $item;
            };
        }
        else {
            $fn = function($item) use (&$return) {
                $return[] = $item;
            };
        }

        array_walk_recursive($array, $fn);
        return $return;
    }


    /**
     * Flatten the keys of an array with a 'glue'.
     *
     * For example:
     * ```
     * [ 'abc' => [
     *     'def' => 123,
     *     'ghi' => 567,
     * ]]
     * ```
     *
     * Becomes:
     * ```
     * [
     *     'abc.def' => 123,
     *     'abc.ghi' => 567,
     * ]
     * ```
     *
     * @param array $array
     * @param string $glue
     * @return array
     */
    public static function flattenKeys(array $array, string $glue = '.'): array
    {
        $flat = [];

        foreach ($array as $key => $value) {
            if (!is_numeric($key) and is_array($value)) {

                // Recurse in!
                $subflat = self::flattenKeys($value, $glue);
                $numeric = false;

                foreach ($subflat as $subkey => $subvalue) {
                    // Our outer value has numeric fields, we'll use that later.
                    // Don't flatten it, but keep going - there might be other
                    // non-numeric keys in here.
                    if (is_numeric($subkey)) {
                        $numeric = true;
                    }
                    else {
                        $flat[$key . $glue . $subkey] = $subvalue;
                    }
                }

                // Got some numeric keys in there so tack on the OG value.
                // We don't want to flatten any keys in the value itself.
                if ($numeric) {
                    $flat[$key] = $value;
                }
            }
            else {
                $flat[$key] = $value;
            }
        }

        return $flat;
    }


    /**
     * Explode a flat array into a nested array based on the keys.
     *
     * For example:
     * ```
     * // From this:
     * [ 'key.sub.value' => $item ]
     *
     * // To this:
     * [ 'key' => [ 'sub' => [ 'value' => $item ] ] ]
     * ```
     *
     * @param array $array
     * @param string $glue
     * @param string|int $index
     * @return array
     */
    public static function explodeKeys(array $array, string $glue = '.', $index = ''): array
    {
        $output = [];

        if (empty($glue)) {
            $glue = '.';
        }

        // This whole thing is _quite_ weird. I'm sure there's a more readable
        // approach using recursion or whatever. But somehow this works.
        foreach ($array as $key => $value) {
            if (is_numeric($key)) continue;

            // Split on glue.
            $parts = explode($glue, trim($key, $glue));

            // Start from the root.
            $cursor = &$output;

            foreach ($parts as $part) {
                /** @var array|string|null $item */
                $item = $cursor[$part] ?? null;

                // We've seen this (as an index) - convert it to an array.
                if ($item and !is_array($item)) {
                    $item = [$index => $item];
                }

                // Create/replace the [key => group] and iterate up.
                $cursor[$part] = $item ?? [];
                $cursor = &$cursor[$part];
            }

            // Found a leaf, tack on the rule.
            if (empty($cursor)) {
                $cursor = $value;
            }
        }

        // A root index key is a bit of an edge-case.
        // @phpstan-ignore-next-line : Doesn't like all the reference business.
        if ($index !== '' and isset($output[''])) {
            $output = [ $index => $output[''] ] + $output;
            // @phpstan-ignore-next-line : Yeah dunno.
            unset($output['']);
        }

        return $output;
    }


    /**
     * Make everything an array, all the way down.
     *
     * This converts any nested arrayables to arrays.
     *
     * @param Arrayable|Traversable|array $array
     * @return array
     */
    public static function toArray($array): array
    {
        if ($array instanceof Arrayable) {
            return $array->toArray();
        }

        foreach ($array as &$item) {
            if ($item instanceof Arrayable) {
                $item = $item->toArray();
                continue;
            }

            if ($item instanceof Traversable) {
                $item = iterator_to_array($item);
                continue;
            }

            if (is_object($item)) {
                $item = (array) $item;
                continue;
            }

            // Like, what else would we do here?
            if (is_resource($item)) {
                $item = '(resource)';
                continue;
            }
        }
        unset($item);

        return $array;
    }


    /**
     * Is this array a numeric/non-associated array.
     *
     * Opposite of isAssociated().
     *
     * Note, this exists in PHP 8.1 as `array_is_list()`.
     *
     * @param mixed $array
     * @return bool
     */
    public static function isNumeric($array): bool
    {
        if (!is_array($array)) return false;

        foreach ($array as $key => $_) {
            if (!is_int($key)) return false;
        }
        return true;
    }


    /**
     * Is this an associated/keyed array.
     *
     * Opposite of isNumeric().
     *
     * @param mixed $array
     * @return bool
     */
    public static function isAssociated($array): bool
    {
        return !self::isNumeric($array);
    }


    /**
     * Query an array.
     *
     * It's a funny concept, but quite powerful.
     *
     * For example, given an array like:
     * ```
     * [
     *    'items' => [
     *      [ 'subitem' => [ 'id' => 123, 'list' => [1,2,3] ] ],
     *      [ 'subitem' => [ 'id' => 456, 'list' => [4,5,6] ] ],
     *    ],
     *    'options' => [
     *       'host' => 'localhost',
     *       'port' => 5060,
     *    ]
     * ]
     *
     * // A query `items.subitem.id` would return:
     * => [ 123, 456 ]
     *
     * // Or `items.subitem.list`:
     * => [ [1, 2, 3], [4, 5, 6] ]
     *
     * // Perhaps `options`:
     * => [ 'host' => 'localhost', 'port' => '5060' ]
     *
     * // And `options.host`:
     * => 'localhost'
     * ```
     *
     * @param array $array
     * @return mixed
     */
    public static function value($array, string $query)
    {
        // Must be an array.
        if (!is_array($array)) return null;

        // Pull apart the query, get our bit, stitch it back together.
        // e.g. 'one.two.three' becomes:
        // key => 'one'
        // query => 'two.three'
        $parts = explode('.', $query);
        $key = array_shift($parts);
        $query = implode('.', $parts);

        // No support for numeric keys.
        if (is_numeric($key)) return null;

        // Look it up.
        $value = $array[$key] ?? null;

        // Not found, quit!
        if ($value === null) return null;

        // End of the query, we found the thing!
        // At this point it doesn't matter the value type. Anything works.
        if (!strlen($query)) return $value;

        // Presented with an array, pick out the numeric bits, if any.
        if (is_array($value)) {
            $values = [];

            foreach ($value as $key => $item) {
                if (!is_numeric($key)) continue;
                if ($item === null) continue;

                $item = self::value($item, $query);
                if ($item === null) continue;

                $values[] = $item;
            }

            // Cheeky flatten for single item arrays.
            while (count($values) === 1 and is_array(@$values[0])) {
                $values = $values[0];
            }

            // Only if we got what we want, otherwise defer to the
            // recursive associated method (below).
            if (!empty($values)) {
                return $values;
            }
        }

        // Recurse on.
        // The value must be a value or associated array.
        return self::value($value, $query);
    }


    /**
     * Shorthand for putting together [key => value] maps.
     *
     * This will silently skip over invalid items:
     * - string/int/null instead of array/object
     * - invalid key types (int/string only)
     * - missing keys/names
     *
     * Example:
     * ```
     * $users = getUsers();
     *
     * // Assuming users are sorted.
     * $options = Arrays::createMap($users, 'id', 'name', 'Choose a user');
     *
     * // Hacky post-sort solution.
     * $options = Arrays::createMap($users, 'id', 'name');
     * asort($options);
     * $options = ['' => 'Choose a user' ] + $options;
     * ```
     *
     * @param iterable $items
     * @param string $key
     * @param string $name
     * @param string|null $select Include a 'choose' option
     * @return array
     */
    public static function createMap($items, string $key, string $name, string $select = null)
    {
        $map = [];

        if ($select) {
            $map[''] = $select;
        }

        foreach ($items as $item) {
            try {
                if (is_object($item)) {
                    $index = $item->$key;
                    $value = $item->$name;
                }
                else if (is_array($item)) {
                    $index = $item[$key];
                    $value = $item[$name];
                }
                else {
                    continue;
                }

                $index = (string) $index;

                $map[$index] = $value;
            }
            catch (Throwable $error) {}
        }

        return $map;
    }


    /**
     * Normalise a list of options with mixed associated / values.
     *
     * E.g.
     * ```
     * $options = [
     *     'field-1',
     *     'field-2' => 'DESC',
     *     'field-3',
     *     'field-4' => 'DESC',
     * ];
     * $options = Arrays::normalizeOptions($options, 'ASC');
     * // Returns:
     * [
     *     'field-1' => 'ASC',
     *     'field-2' => 'DESC',
     *     'field-3' => 'ASC',
     *     'field-4' => 'DESC',
     * ];
     * ```
     *
     * @param iterable $items
     * @param mixed $default
     * @return array
     */
    public static function normalizeOptions($items, $default): array
    {
        $output = [];

        foreach ($items as $key => $value) {
            if (is_numeric($key)) {
                $output[$value] = $default;
            }
            else {
                $output[$key] = $value;
            }
        }

        return $output;
    }


    /**
     * Load a php config file.
     *
     * A config can be in one of two forms.
     *
     * 1. Create a `$config` variable:
     * ```
     * $config['key'] = 'value';
     * // EOF
     * ```
     *
     * 2. Or return an array:
     * ```
     * return [
     *     'key' => 'value',
     * ];
     * ```
     *
     * Note, a missing file is indistinguishable from an invalid file. Be sure
     * to use file_exists() first, if you care about that sort of thing.
     *
     * @param string $path
     * @return array|null `null` if the file is invalid or missing.
     */
    public static function config(string $path)
    {
        static $cache = [];
        $output = $cache[$path] ?? null;

        if ($output === null) {
            $output = (function($_path) {
                $alt = @include $_path;
                //This is literally a defines check. Why are you mad?
                // @phpstan-ignore-next-line
                return $config ?? $alt;
            })($path);
        }

        if (!is_array($output)) return null;
        $cache[$path] = $output;
        return $output;
    }
}
