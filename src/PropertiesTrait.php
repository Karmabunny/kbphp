<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use ReflectionClass;
use ReflectionNamedType;
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
     * Get a list of default properties for this object.
     *
     * By default this is all non-static public properties with compile time
     * default values. That is, defined in the class definition.
     *
     * @return array [ name => value ]
     */
    public static function getPropertyDefaults(): array
    {
        static $_FIELDS = [];
        $defaults = $_FIELDS[static::class] ?? null;

        if ($defaults === null) {
            $reflect = new ReflectionClass(static::class);
            $properties = $reflect->getDefaultProperties();

            foreach ($properties as $name => $value) {
                $property = $reflect->getProperty($name);

                // We only want non-null, public, instance properties.
                if ($value === null) continue;
                if ($property->isStatic()) continue;
                if (!$property->isPublic()) continue;

                $defaults[$name] = $value;
            }

            $_FIELDS[static::class] = $defaults;
        }

        return $defaults;
    }


    /**
     * Get a list of properties, with respective types (if available).
     *
     * Object properties can have strict types. If not typed, all properties
     * will be `mixed`.
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
                $type_name = null;

                $type = $property->getType();
                if ($type instanceof ReflectionNamedType) {
                    $type_name = $type->getName();
                }

                $fields[$name] = $type_name ?? 'mixed';
            }

            $_FIELDS[static::class] = $fields;
        }

        return $fields;
    }
}
