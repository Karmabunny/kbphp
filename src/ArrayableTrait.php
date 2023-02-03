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
 */
trait ArrayableTrait
{

    public abstract function getIterator(): Traversable;


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
     * Note, if not implementing the NewArrayable interface, this method will
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
     * @see NewArrayable
     * @see ArrayableTrait
     * @return callable[]
     */
    public function fields(): array
    {
        if ($this instanceof NewArrayable) {
            // Don't use iterable here.
            // However, it IS used for the backwards compat later.
            $fields = Reflect::getProperties($this, false);

            foreach ($fields as &$field) {
                $field = true;
            }
            unset($field);
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
     */
    public function extraFields(): array
    {
        return [];
    }


    /**
     * Convert this object to an array.
     *
     * Specify a 'filter' to restrict the fields included in the output. This can only
     *
     * Specify an 'extra' to include additional fields, including those not
     * included in `fields()` and any virtual fields defined in `extraFields()`.
     *
     * @param string[]|null $filter
     * @param string[]|null $extra
     * @return array
     */
    public function toArray(array $filter = null, array $extra = null): array
    {
        if ($this instanceof NewArrayable) {
            $fields = $this->fields();
        }
        else {
            $fields = Reflect::getProperties($this, true);

            foreach ($fields as &$field) {
                $field = true;
            }
            unset($field);

            $fields = array_merge($fields, $this->fields());
        }

        // Add extra fields.
        if ($extra) {
            $extra = array_fill_keys($extra ?? [], true);

            // Extract any virtual fields.
            $extraFields = $this->extraFields();
            $extraFields = array_intersect_key($extraFields, $extra);

            // Add and re-apply any 'extras' not already in extraFields().
            $fields = array_merge($fields, $extra, $extraFields);
        }

        // Apply filters.
        if ($filter) {
            $filter = array_fill_keys($filter ?? [], true);

            // Extract any virtual fields.
            $fields = array_intersect_key($fields, $filter);

            // Re-apply any in 'filter' not already in fields().
            $fields = array_merge($filter, $fields);
        }

        foreach ($fields as $key => $item) {
            // Invalid config.
            if (is_numeric($key)) {
                continue;
            }

            // False/null/empty - nothing to see here.
            if (!$item) {
                continue;
            }

            if (is_callable($item)) {
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

            // A regular field - look it up.
            if ($item === true) {
                $item = $this->$key ?? null;

                // Keep skipping null fields for compatibility.
                if ($item === null) {
                    continue;
                }
            }

            // We're not piping resources around like idiots here.
            if (is_resource($item)) {
                continue;
            }

            // Recurse into nested arrayables.
            if (
                is_array($item)
                or $item instanceof Arrayable
                or $item instanceof Traversable
            ) {
                $item = Arrays::toArray($item);
            }

            $array[$key] = $item;
        }

        return $array;
    }


    /** @inheritdoc */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
