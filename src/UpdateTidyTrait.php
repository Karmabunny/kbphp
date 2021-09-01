<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use ReflectionClass;
use ReflectionProperty;

/**
 * This modifies the behaviour of a DataObject/Collection so that only
 * properties defined in the class are updated.
 *
 * The default implementation will create _new_ fields that aren't typed. This
 * trait will only update fields that are explicitly defined. Unknown fields
 * are siliently ignored.
 *
 * For a more aggressive approach {@see UpdateStrictTrait}.
 *
 * @package karmabunny\kb
 */
trait UpdateTidyTrait
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

        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $fields)) continue;
            $this->$key = $value;
        }
    }
}
