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
     * These are virtual fields for use in toArray().
     *
     * Define callbacks in here for extra things that you might want
     * included in the array version of this collection.
     *
     * @example
     *   return [
     *       'virtual_thing' => [$this, 'getMyVirtualThing'],
     *       'inline_thing' => function() {
     *            return 'hey look: ' . time();
     *       },
     *   ];
     *
     * @return callable[]
     */
    public function fields(): array
    {
        return [];
    }


    /**
     *
     * @param array|null $fields
     * @return array
     */
    public function toArray(array $fields = null): array
    {
        $array = [];
        foreach ($this as $key => $item) {
            // Skip unset/null properties.
            // Ideally, we would still include explicitly set 'null' values.
            // Buuuut that's a PHP 7.4 feature.
            if (!isset($this->$key)) continue;

            // Filtering.
            if (!empty($fields) and !in_array($key, $fields)) {
                continue;
            }

            // Recursively convert arrayables.
            if (is_array($item) or $item instanceof Arrayable) {

                // Attempt to prevent infinite recursives.
                if ($item === $this) {
                    continue;
                }

                $item = Arrays::toArray($item);
            }

            $array[$key] = $item;
        }

        // Virtual fields.
        foreach ($this->fields() as $key => $item) {
            // Invalid.
            if (is_numeric($key)) {
                continue;
            }

            // Filtering.
            if (!empty($fields) and !in_array($key, $fields)) {
                continue;
            }

            // Call it.
            if (is_callable($item)) {
                $item = $item();
            }

            // Someone typoed a callback array - abort!
            // Or it's somehow the same object, either way it'll end up
            // infinitely recursive so let's skip it.
            if (
                $item === $this
                or (is_array($item) and ($item[0] ?? null) === $this)
            ) {
                continue;
            }

            // Recursively convert arrayables.
            if (is_array($item) or $item instanceof Arrayable) {
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
