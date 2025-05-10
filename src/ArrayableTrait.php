<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Traversable;

/**
 * Adds a `toArray()` method that returns an array copy of this object.
 *
 * - Nested 'Arrayable' objects are also converted to arrays.
 * - Object can declare a `fields()` to include virtual fields.
 * - This requires an iterable object (`IteratorAggregate` interface).
 *
 * Objects should extend the {@see ArrayableFields} interface to get additional
 * type safety and some goodies from extraFields().
 */
trait ArrayableTrait
{

    /**
     * These are fields for use in `toArray()`.
     *
     * By default this will include all public fields of the object.
     *
     * Given `'name' => true` a field will appear in the array output. Given
     * `false|null|0|unset|...` the field will not be present.
     *
     * Define a pair like `'name' => callback` create a 'virtual' field. This
     * can override existing properties or create whole new fields.
     *
     * Note, if not implementing the ArrayableFields interface, this method will
     * only define 'virtual' fields. That said, inheriting the parent fields
     * is safe and will future-proof the code.
     *
     * ```
     * // Inheriting fields from the parent:
     * $fields = parent::fields();
     * $fields['hide_this_field'] = false;
     * unset($fields['this_also_works']);
     * return $fields;
     *
     * // Declaring explicit fields, ignoring inherited:
     * return [
     *   'real_field' => true,
     *   'and_this_field' => true,
     * ];
     *
     * // Adding virtual fields:
     * $fields = parent::fields();
     * $fields['virtual_thing'] = [$this, 'getMyVirtualThing'];
     * $fields['inline_thing'] = function() {
     *    return 'hey look: ' . time();
     * };
     * return $fields;
     * ```
     *
     * @see ArrayableFields
     * @see ArrayableTrait
     * @return (callable|bool)[]
     */
    public function fields(): array
    {
        if ($this instanceof ArrayableFields) {
            // Don't use iterable here.
            // However, it IS used for the backwards compat later.
            $fields = Reflect::getProperties($this, false);
            $fields = array_fill_keys(array_keys($fields), true);
            return $fields;
        }
        else {
            return [];
        }
    }


    /**
     * Define extra fields that you _might_ want included in the `toArray()`
     * output.
     *
     * These are virtual fields that are not included by default like the
     * ones defined in `fields()`. Instead, use the second parameter of
     * the `toArray(null, ['name'])` method to specify which extra fields
     * you want in the output.
     *
     * A virtual field is defined as a pair like `'name' => callback`.
     *
     * @return callable[]
     * @see ArrayableFields
     * @see ArrayableTrait
     * @return callable[]
     */
    public function extraFields(): array
    {
        return [];
    }


    /**
     * Convert this object to an array.
     *
     * Specify a 'filter' to restrict the fields included in the output.
     *
     * Specify an 'extra' to include additional fields if implementing the
     * ArrayableFields interface. This will add properties that were excluded
     * from `fields()` and includes any virtual fields defined in `extraFields()`.
     *
     * @param string[]|null $filter
     * @param string[]|null $extra
     * @param bool $nulls - include null fields in the output
     * @return array
     */
    public function toArray(?array $filter = null, ?array $extra = null, bool $nulls = false): array
    {
        if ($this instanceof ArrayableFields) {
            $fields = $this->fields();
        }
        else {
            $fields = Reflect::getProperties($this, true);
            $fields = array_fill_keys(array_keys($fields), true);
            $fields = array_merge($fields, $this->fields());
        }

        // Apply filters.
        if ($filter) {
            $filter_keys = Arrays::keyRoots($filter);
            $filter_keys = array_fill_keys($filter_keys, true);

            // Extract any virtual fields.
            $fields = array_intersect_key($fields, $filter_keys);

            // Re-apply any in 'filter' not already in fields().
            $fields = array_merge($filter_keys, $fields);
        }

        // Add extra fields.
        if ($extra) {
            $extra_keys = Arrays::keyRoots($extra, true);
            $extra_keys = array_fill_keys($extra_keys, true);

            $fields = array_merge($fields, $extra_keys);

            if ($this instanceof ArrayableFields) {
                $extra_fields = $this->extraFields();
                $extra_fields = array_intersect_key($extra_fields, $extra_keys);

                $fields = array_merge($fields, $extra_fields);
            }
        }

        $array = [];

        foreach ($fields as $key => $item) {
            /** @var mixed $item */

            // Invalid config.
            if (is_numeric($key)) {
                continue;
            }

            // False/null/empty - nothing to see here.
            if (!$item) {
                continue;
            }

            // A regular field - look it up.
            if ($item === true) {
                $item = $this->$key ?? null;

                // 'No nulls' by default for compatibility.
                if ($item === null and !$nulls) {
                    continue;
                }
            }
            // A virtual field - but only if it doesn't exist as data.
            else if (is_callable($item)) {
                $item = $item();
            }

            // Prevent self-recursion.
            if ($item === $this) {
                continue;
            }

            // Limited protection from [$this, 'typo'].
            if (is_array($item) and ($item[0] ?? null) === $this) {
                continue;
            }

            // We're not piping resources around like idiots here.
            if (is_resource($item)) {
                continue;
            }

            // Recurse + pass through null flags.
            if (
                $this instanceof ArrayableFields
                and $item instanceof ArrayableFields
            ) {
                $item = $item->toArray(null, null, $nulls);
            }

            // Recurse into nested arrays or arrayables.
            else if (
                is_array($item)
                or $item instanceof Arrayable
                or $item instanceof Traversable
            ) {
                $next_filter = $filter ? Arrays::keyChildren($key, $filter) : null;
                $next_extra = $extra ? Arrays::keyChildren($key, $extra, true) : null;

                $item = Arrays::toArray($item, $next_filter, $next_extra);

                if (empty($item)) {
                    continue;
                }
            }

            $array[$key] = $item;
        }

        return $array;
    }

}
