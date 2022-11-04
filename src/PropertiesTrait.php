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

    /**
     * Get a list of properties for this object.
     *
     * By default this is a set of non-static public properties, but can be
     * overridden by child classes that might want to show or hide other data.
     *
     * This is used by:
     * - {@see SerializeTrait}
     * - {@see UpdateTidyTrait}
     * - {@see UpdateStrictTrait}
     *
     * @return string[]
     */
    public static function getProperties(): array
    {
        $properties = static::getPropertyTypes();
        return array_keys($properties);
    }


    /**
     * Get a list of properties, with respective types (if available).
     *
     * Beginning with PHP 7.4 object properties can have strict types. If not
     * typed or running a older PHP all properties will be `mixed`.
     *
     * @return string[] [ name => type ]
     */
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
