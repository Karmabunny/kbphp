<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;

/**
 * Base class for virtual property attributes.
 *
 * @see VirtualProperty
 * @see VirtualObject
 * @see VirtualArray
 *
 * @package karmabunny\kb
 */
abstract class VirtualPropertyBase
{

    /** @var ReflectionProperty */
    protected $reflect;


    /**
     * Apply the virtual property to the target object.
     *
     * @param object $target
     * @param mixed $value
     * @return bool
     */
    public abstract function apply(object $target, $value): bool    ;


    /**
     * The target property name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->reflect->getName();
    }


    /**
     * Is this property nullable?
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        $type = $this->reflect->getType();
        return ($type and $type->allowsNull());
    }


    /**
     * Parse the virtual properties for a target object.
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

            $item = $attributes[0]->newInstance();
            $item->reflect = $property;

            $virtuals[$name] = $item;
        }

        return $virtuals;
    }
}
