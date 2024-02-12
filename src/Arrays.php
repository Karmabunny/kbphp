<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Generator;
use RecursiveIteratorIterator;
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
     * A recursive mode that doesn't visit intermediate nodes.
     */
    const LEAVES_ONLY = RecursiveIteratorIterator::LEAVES_ONLY;

    /**
     * A recursive mode that first visits intermediate nodes and _then_
     * visits each child node.
     */
    const SELF_FIRST = RecursiveIteratorIterator::SELF_FIRST;

    /**
     * A recursive mode that first visits leaf nodes and _then_ visits
     * the parent node.
     */
    const CHILD_FIRST = RecursiveIteratorIterator::CHILD_FIRST;

    /**
     * Remove empty arrays when filtering recursively.
     */
    const DISCARD_EMPTY_ARRAYS = 1024;


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
     * @template T
     * @param T[]|iterable<T> $iterable
     * @return T|null
     */
    public static function first($iterable)
    {
        list( , $value) = self::firstPair($iterable);
        return $value;
    }


    /**
     * Last item in the iterable.
     *
     * @template T
     * @param T[]|iterable<T> $iterable
     * @return T|null
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
     * @template T
     * @param T[]|iterable<T> $array
     * @return iterable<T>
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
     * Reduce an array in reverse.
     *
     * @template T
     * @param iterable $array
     * @param callable $fn (carry, value, key)
     * @param T|null $initial
     * @return T
     */
    public static function reverseReduce($array, $fn, $initial = null)
    {
        $reversed = self::reverse($array);
        $carry = $initial;

        foreach ($reversed as $key => $item) {
            $carry = $fn($carry, $item, $key);
        }

        return $carry;
    }


    /**
     * Merge arrays while preserving keys.
     *
     * This uses array concat instead of merge, such that:
     *
     * ```
     * array_merge([3 => 'one'], [3 => 'two'])
     * // => ['one', two']
     *
     * mergeKeyed([3 => 'one'], [3 => 'two'])
     * // => [3 => 'two']
     * ```
     *
     * @template T
     * @param T[] $arrays
     * @return T[]
     */
    public static function mergeKeyed(...$arrays)
    {
        $reversed = self::reverse($arrays);

        $output = [];

        foreach ($reversed as $item) {
            $output = $item + $output;
        }

        return $output;
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
     * @template T
     * @param T[]|iterable<T> $iterable
     * @param callable $fn ($value, $key) => bool
     * @return T|null
     */
    public static function find($iterable, callable $fn)
    {
        foreach ($iterable as $key => $item) {
            if ($fn($item, $key)) return $item;
        }

        return null;
    }


    /**
     * Find a matching item key.
     *
     * Same behaviour as 'find()' only this returns the index/key instead.
     *
     * The callable is provided with the value FIRST and the key SECOND.
     *
     * @template T
     * @param T[]|iterable<T> $iterable
     * @param callable $fn ($value, $key) => bool
     * @return T|null
     */
    public static function findKey($iterable, callable $fn)
    {
        foreach ($iterable as $key => $item) {
            if ($fn($item, $key)) return $key;
        }

        return null;
    }


    /**
     * Find the numeric index of an associative item.
     *
     * @param array $array
     * @param string $key
     * @return null|int
     */
    public static function indexOf(array $array, string $key)
    {
        $index = array_search($key, array_keys($array));

        if ($index === false) {
            return null;
        }

        return (int) $index;
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
     * Filter multi-dimensional arrays.
     *
     * Both value and key are always passed to the callback.
     *
     * If the callback is `null` without a specified `$mode`
     * the `DISCARD_EMPTY_ARRAYS` mode is implicitly active.
     *
     * Be aware, numeric arrays will not 're-settle' their keys. Items will
     * retain their index position even with gaps from removed items. This is
     * the same behaviour as `array_filter`.
     *
     * For example:
     *
     * ```
     * $arr = [ 'a', '', 'c' ];
     * $filtered = array_filter($arr);
     * // => [ 0 => 'a', 2 => 'c' ]
     * ```
     *
     * @template T
     * @param T[] $array
     * @param callable|null $callback
     * @param int|null $mode LEAVES_ONLY (default), SELF_FIRST, CHILD_FIRST, DISCARD_EMPTY_ARRAYS
     * @return T[]
     */
    public static function filterRecursive(array $array, callable $callback = null, int $mode = null): array
    {
        if ($mode === null) {
            $mode = self::LEAVES_ONLY;

            if ($callback === null) {
                $mode |= self::DISCARD_EMPTY_ARRAYS;
            }
        }

        if ($callback === null) {
            $callback = function($value) {
                return !empty($value);
            };
        }

        foreach ($array as $key => &$value) {
            if (is_array($value)) {

                if ($mode & self::SELF_FIRST and !$callback($value, $key)) {
                    if ($mode & self::DISCARD_EMPTY_ARRAYS) {
                        unset($array[$key]);
                    }
                    else {
                        $value = [];
                    }
                    continue;
                }

                $value = self::filterRecursive($value, $callback, $mode);

                if ($mode & self::DISCARD_EMPTY_ARRAYS and empty($value)) {
                    unset($array[$key]);
                    continue;
                }

                if ($mode & self::CHILD_FIRST and !$callback($value, $key)) {
                    if ($mode & self::DISCARD_EMPTY_ARRAYS) {
                        unset($array[$key]);
                    }
                    else {
                        $value = [];
                    }
                    continue;
                }
            }
            else {
                if (!$callback($value, $key)) {
                    unset($array[$key]);
                }
            }
        }
        return $array;
    }


    /**
     * Reduce the array to a subset, as defined by the keys parameter.
     *
     *
     * @template T
     * @param T[] $array
     * @param string[] $keys
     * @param bool $fill Replace missing keys with null.
     * @return T[]
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
     * Key modification can be done with a reference `&$key` argument.
     *
     * ```
     * // Basic usage
     * Arrays::mapWithKeys($list, fn($item, $key) => $key . $item);
     *
     * // OR to modify keys
     * Arrays::mapWithKeys($list, function($item, &$key) {
     *     $key = $item->id;
     *     return $item;
     * });
     * ```
     *
     * Filter callbacks used with this method can be re-used (without key
     * modification) for `array_map` or `mapRecursive`.
     *
     * @param array $array
     * @param callable $fn (item, key) => item
     * @return array
     */
    public static function mapWithKeys(array $array, $fn): array
    {
        $items = [];

        foreach ($array as $key => $item) {
            $item = $fn($item, $key);
            $items[$key] = $item;
        }

        return $items;
    }


    /**
     * A weirder version of `mapWithKeys()`.
     *
     * Instead this expects the callback to return a key-value pair, e.g.
     *
     * ```
     * Arrays::mapKeys($list, fn($item, $key) => [$key, $item]);
     * ```
     *
     * @deprecated use mapWithKeys() - what a mess.
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
     * A recursive version of `array_map`.
     *
     * Caution when using `CHILD_FIRST`; do not map leaves into arrays - you may
     * trigger an infinite recursion.
     *
     * Modifying the `$key` callback argument, like `mapWithKeys`, is undefined
     * behaviour. Best avoid.
     *
     * @param array $array
     * @param callable $fn ($value, $key) => $value
     * @param int $mode LEAVES_ONLY (default), SELF_FIRST, CHILD_FIRST
     * @return array
     */
    public static function mapRecursive(array $array, $fn, $mode = self::LEAVES_ONLY): array
    {
        $process = null;
        $process = function($rootkey, array $array, $fn, $mode) use (&$process) {
            if ($mode === self::SELF_FIRST) {
                $array = $fn($array, $rootkey);

                if (!is_array($array)) {
                    return $array;
                }
            }

            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $value = $process($key, $value, $fn, $mode);
                }
                else {
                    $value = $fn($value, $key);
                }

                $array[$key] = $value;
            }

            if ($mode === self::CHILD_FIRST) {
                $array = $fn($array, $rootkey);
            }

            return $array;
        };

        return $process(null, $array, $fn, $mode);
    }


    /**
     * Shuffle an array, optionally preserving the keys.
     *
     * Yes, this is just the native shuffle() but it's also non-destructive
     * with key preserving options.
     *
     * @template T
     * @param T[]|iterable<T> $array
     * @param bool $preserve_keys
     * @return T[]
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
     * @param iterable $array
     * @param bool $keys
     * @param int $depth
     * @return array
     */
    public static function flatten($array, $keys = false, int $depth = 25): array
    {
        $return = [];

        if ($keys) {
            foreach ($array as $key => $value) {
                if (is_iterable($value)) {
                    if ($depth <= 1) {
                        continue;
                    }

                    $value = self::flatten($value, true, $depth - 1);
                    foreach ($value as $key => $sub) {
                        $return[$key] = $sub;
                    }
                }
                else {
                    $return[$key] = $value;
                }
            }
        }
        else {
            foreach ($array as $value) {
                if (is_iterable($value)) {
                    if ($depth <= 1) {
                        continue;
                    }

                    $value = self::flatten($value, false, $depth - 1);
                    foreach ($value as $sub) {
                        $return[] = $sub;
                    }
                }
                else {
                    $return[] = $value;
                }
            }
        }

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
        if (!is_array($array)) {
            return false;
        }

        $i = 0;
        foreach ($array as $k => $_) {
            if ($k !== $i++) {
                return false;
            }
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


    /**
     * Create a comparator function.
     *
     * This is compatible with the {@see Sortable} interface.
     *
     * This explicitly does NOT compare objects using the natural PHP
     * sorting behaviour, described here:
     *
     * https://www.php.net/manual/en/language.oop5.object-comparison.php#98725
     *
     * @param int $dir SORT_ASC|SORT_DESC
     * @param string $mode 'default'
     * @return callable(mixed, mixed): int
     */
    public static function createSort(int $dir = SORT_ASC, string $mode = 'default')
    {
        $dir = $dir === SORT_DESC ? -1 : 1;

        return function($a, $b) use ($dir, $mode) {
            // Shortcut.
            if ($a === $b) {
                return 0;
            }

            if ($a instanceof Sortable) {
                return $a->compare($b, $mode) * $dir;
            }

            if ($b instanceof Sortable) {
                return $b->compare($a, $mode) * $dir * -1;
            }

            $is_a = is_object($a);
            $is_b = is_object($b);

            // Disable natural object ordering.
            if ($is_a and $is_b) {
                return 0;
            }

            // Put unsortable objects last.
            if ($is_a or $is_b) {
                return ($is_a ? 1 : -1) * $dir;
            }

            // Natural scalar sorting.
            return ($a <=> $b) * $dir;
        };
    }


    /**
     * Sort an array using the {@see Sortable} interface.
     *
     * @param array $array
     * @param bool $preserve_keys
     * @param int $dir SORT_ASC|SORT_DESC
     * @return void
     */
    public static function sort(array &$array, bool $preserve_keys = false, int $dir = SORT_ASC)
    {
        $fn = self::createSort($dir);

        if ($preserve_keys) {
            uasort($array, $fn);
        }
        else {
            usort($array, $fn);
        }
    }


    /**
     * Sort an array and return it.
     *
     * Using the {@see Sortable} interface.
     *
     * @template T
     * @param T[] $array
     * @param bool $preserve_keys
     * @param int $dir SORT_ASC|SORT_DESC
     * @return T[]
     */
    public static function sorted(array $array, bool $preserve_keys = false, int $dir = SORT_ASC): array
    {
        self::sort($array, $preserve_keys, $dir);
        return $array;
    }

}
