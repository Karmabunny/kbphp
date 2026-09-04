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
 * Base class for cast attributes.
 *
 * @package karmabunny\kb
 */
abstract class Cast
{

    protected object $target;

    protected string $property;

    protected bool $nullable = false;


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
            $cast = self::find($target, $property);

            if (!$cast) {
                continue;
            }

            $virtuals[$property->getName()] = $cast;
        }

        return $virtuals;
    }


    /**
     * Find a cast attribute on a property.
     *
     * @param object $target
     * @param ReflectionProperty|string $property
     * @return null|static
     */
    public static function find(object $target, ReflectionProperty|string $property): ?static
    {
        if (is_string($property)) {
            $property = new ReflectionProperty($target, $property);
        }

        $attributes = $property->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

        $attribute = end($attributes);

        if ($attribute === false) {
            return null;
        }

        $type = $property->getType();

        $cast = $attribute->newInstance();
        $cast->nullable = ($type and $type->allowsNull());
        $cast->target = $target;
        $cast->property = $property->getName();
        return $cast;
    }
}
