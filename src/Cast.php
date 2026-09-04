<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;

/**
 *
 * @package karmabunny\kb
 */
abstract class Cast
{


    protected $nullable = false;


    /**
     * Build a value.
     *
     * @param mixed $value
     * @return mixed
     */
    abstract public function build(mixed $value): mixed;


    /**
     * Is this property nullable?
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }


    /**
     * Find all cast attributes on a target object.
     *
     * @param object $target
     * @return array<string,static> [ property => virtual ]
     */
    public static function parse(object $target): array
    {
        $reflect = new ReflectionObject($target);

        $virtuals = [];

        foreach ($reflect->getProperties() as $property) {
            $name = $property->getName();
            $attributes = $property->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

            if (empty($attributes)) {
                continue;
            }

            $type = $property->getType();

            $item = $attributes[0]->newInstance();
            $item->nullable = ($type and $type->allowsNull());

            $virtuals[$name] = $item;
        }

        return $virtuals;
    }


    /**
     * Find a cast attribute on a property.
     *
     * @param ReflectionProperty|array{0:class-string,1:string} $property
     * @return null|static
     */
    public static function find(ReflectionProperty|array $property): ?static
    {
        if (is_array($property)) {
            [$class, $name] = $property;
            $property = new ReflectionProperty($class, $name);
        }

        $attributes = $property->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

        $attribute = end($attributes);

        if ($attribute === false) {
            return null;
        }

        $type = $property->getType();

        $cast = $attribute->newInstance();
        $cast->nullable = ($type and $type->allowsNull());
        return $cast;
    }
}
