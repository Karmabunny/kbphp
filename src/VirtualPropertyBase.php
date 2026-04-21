<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionAttribute;
use ReflectionClass;
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
     * @return bool the property was applied
     */
    public abstract function apply(object $target, $value): bool;


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
     * Parse the virtual properties for a target class.
     *
     * @param class-string $target
     * @return array<string,static> [ property => virtual ]
     */
    public static function parse(string $target): array
    {
        $reflect = new ReflectionClass($target);

        $virtuals = [];

        foreach ($reflect->getProperties() as $property) {
            $name = $property->getName();
            // @phpstan-ignore-next-line : PHP8 only.
            $attributes = $property->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

            if (empty($attributes)) {
                continue;
            }

            $property->setAccessible(true);

            $item = $attributes[0]->newInstance();
            $item->reflect = $property;

            $virtuals[$name] = $item;
        }

        return $virtuals;
    }
}
