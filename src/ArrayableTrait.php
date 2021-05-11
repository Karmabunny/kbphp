<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Traversable;

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
                $item = Arrays::toArray($item);
            }

            $array[$key] = $item;
        }

        // Virtual fields.
        foreach ($this->fields() as $key => $item) {
            // Filtering.
            if (!empty($fields) and !in_array($key, $fields)) {
                continue;
            }

            // Call it.
            if (is_callable($item)) {
                $item = $item();
            }

            // Recursively convert arrayables.
            if (is_array($item) or $item instanceof Arrayable) {
                $item = Arrays::toArray($item);
            }

            $array[$key] = $item;
        }

        return $array;
    }
}
