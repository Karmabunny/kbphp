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
    public static function getProperties()
    {
        static $fields;

        if ($fields === null) {
            $fields = [];

            $reflect = new ReflectionClass(static::class);
            $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                if ($property->isStatic()) continue;
                $fields[] = $property->getName();
            }
        }

        return $fields;
    }
}
