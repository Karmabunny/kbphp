<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * This modifies the behaviour of a DataObject/Collection so that only
 * properties defined in the class are updated.
 *
 * This extends the behaviour of {@see UpdateTidyTrait}, where it will throw
 * errors if a field is missing instead of silently ignoring it.
 *
 * @package karmabunny\kb
 */
trait UpdateStrictTrait
{

    /**
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        static $fields;

        if ($fields === null) {
            $reflect = new ReflectionClass(static::class);
            $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC ^ ReflectionProperty::IS_STATIC);

            $fields = [];
            foreach ($properties as $property) {
                $field = $property->getName();
                $fields[$field] = true;
            }
        }

        $errors = [];

        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $fields)) {
                $errors[] = $key;
                continue;
            }

            $this->$key = $value;
        }

        // Throw all at once.
        if ($errors) {
            $count = count($errors);
            $errors = array_slice($errors, 0, 25);
            $errors = implode(', ', $errors);

            if ($count > 25) {
                $errors .= ' ...';
            }

            throw new InvalidArgumentException("Unknown fields ({$count}): {$errors}");
        }
    }
}
