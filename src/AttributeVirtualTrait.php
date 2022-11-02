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
 * This modifies the behaviour of a DataObject/Collection for updating complex
 * properties, such as arrays and objects.
 *
 * Attach a {@see VirtualProperty} to a property with the target method name.
 *
 * @package karmabunny\kb
 */
trait AttributeVirtualTrait
{

    /**
     * Apply the virtual converters to the all properties.
     *
     * Recommended placements:
     *  - `__clone()`
     *  - `update()`
     *
     * @return void
     */
    protected function applyVirtual()
    {
        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $property) {
            $virtual = null;

            // Parse some attributes.
            if (PHP_VERSION_ID > 80000) {
                $attributes = $property->getAttributes(VirtualProperty::class);

                foreach ($attributes as $attribute) {
                    /** @var VirtualProperty $virtual */
                    $virtual = $attribute->newInstance();
                    break;
                }
            }

            // Nothing found in the attributes, have a look in the docs.
            if (!$virtual) {
                $virtual = VirtualProperty::parseDoc($property->getDocComment() ?: '');
            }

            // Still no, move on.
            if (!$virtual) {
                continue;
            }

            // Check it first.
            if (!method_exists($this, $virtual->method)) {
                throw new InvalidArgumentException("Virtual property method not found: {$virtual->method}");
            }

            $this->{$virtual->method}($this->{$property->getName()});
        }
    }
}
