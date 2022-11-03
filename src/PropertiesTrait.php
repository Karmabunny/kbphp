<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use ReflectionClass;
use ReflectionProperty;

/**
 * Get a list of property fields for this class.
 *
 * @package karmabunny\kb
 */
trait PropertiesTrait
{
    public static function getProperties(): array
    {
        $properties = static::getPropertyTypes();
        return array_keys($properties);
    }


    public static function getPropertyTypes(): array
    {
        static $_FIELDS = [];
        $fields = $_FIELDS[static::class] ?? null;

        if ($fields === null) {
            $fields = [];

            $reflect = new ReflectionClass(static::class);
            $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                if ($property->isStatic()) continue;

                $name = $property->getName();
                $type = null;

                if (PHP_VERSION_ID >= 74000) {
                    $type = $property->getType();
                    if ($type !== null) {
                        $type = $type->getName();
                    }
                }

                $fields[$name] = $type ?? 'mixed';
            }

            $_FIELDS[static::class] = $fields;
        }

        return $fields;
    }
}
