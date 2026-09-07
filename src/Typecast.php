<?php
namespace karmabunny\kb;

use BackedEnum;
use karmabunny\interfaces\LogSourceInterface;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Throwable;
use UnitEnum;

/**
 * Typecasting for objects.
 *
 * This does scalar-to-scalar casts and will apply the {@see Cast} attribute, if present.
 *
 * @see Cast
 * @see TypecastTrait
 *
 * @package karmabunny\kb
 */
class Typecast implements LogSourceInterface
{
    use LoggerTrait;


    /**
     *
     * @param object $target
     * @return void
     */
    public function __construct(public object $target)
    {
    }


    /**
     * Cast a value for this object.
     *
     * - between applicable scalar types
     * - properties that have {@see Cast} attributes
     *
     * @param string $field
     * @param mixed $value mutable
     * @return bool true if the type matches, false otherwise
     */
    public function cast(string $field, &$value): bool
    {
        try {
            $property = new ReflectionProperty($this->target, $field);
        }
        catch (ReflectionException $error) {
            $this->log($error, Log::LEVEL_WARNING, static::class);
            return false;
        }

        // See if there's a cast attribute first.
        if ($cast = Cast::find($this->target, $property)) {
            try {
                $value = $cast->build($value);
                return true;
            }
            catch (Throwable $error) {
                $this->log($error, Log::LEVEL_ERROR, static::class);
                return false;
            }
        }

        $parentType = $property->getType();
        $subTypes = [];

        // Single named type.
        if ($parentType instanceof ReflectionNamedType) {
            $subTypes[$parentType->getName()] = $parentType;
        }
        // List of named types, but discard any intersections.
        else if ($parentType instanceof ReflectionUnionType) {
            foreach ($parentType->getTypes() as $type) {
                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                $subTypes[$type->getName()] = $type;
            }

            if (empty($subTypes)) {
                return false;
            }
        }
        // We can't really handle these automatically.
        else if ($parentType instanceof ReflectionIntersectionType) {
            return false;
        }
        // Without type information anything goes.
        else {
            return true;
        }

        // Strict empty, not PHP empty.
        $is_empty = ($value === '' or $value === null);

        if ($parentType->allowsNull() and $is_empty) {
            $value = null;
            return true;
        }

        // Special conditions.
        if (count($subTypes) > 1) {
            if (is_numeric($value)) {
                if (isset($subTypes['int'])) {
                    $value = (int) $value;
                    return true;
                }

                if (isset($subTypes['float'])) {
                    $value = (float) $value;
                    return true;
                }
            }

            if (isset($subTypes['bool'])) {
                $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool !== null) {
                    $value = $bool;
                    return true;
                }
            }

            if (
                isset($subTypes['int'])
                and $value instanceof BackedEnum
                and is_int($value->value)
            ) {
                $value = $value->value;
                return true;
            }
        }

        foreach ($subTypes as $type) {
            // Explicitly anything goes.
            if ($type->getName() === 'mixed') {
                return true;
            }

            // Full match, move on.
            if (get_debug_type($value) === $type->getName()) {
                return true;
            }

            if (is_iterable($value) and $type->getName() === 'iterable') {
                return true;
            }

            if (
                (is_string($value) or is_int($value))
                and is_subclass_of($type->getName(), BackedEnum::class)
            ) {
                try {
                    $value = $type->getName()::from($value);
                    return true;
                }
                catch (Throwable $error) {
                    if ($type->allowsNull()) {
                        $this->log($error, Log::LEVEL_WARNING, static::class);
                        $value = null;
                        return true;
                    }
                    else {
                        $this->log($error, Log::LEVEL_ERROR, static::class);
                        return false;
                    }
                }
            }

            if (
                is_string($value)
                and is_subclass_of($type->getName(), UnitEnum::class)
            ) {
                foreach ($type->getName()::cases() as $case) {
                    if ($case->name === $value) {
                        $value = $case;
                        return true;
                    }
                }

                if ($type->allowsNull()) {
                    $value = null;
                    return true;
                }

                return false;
            }

            if (is_object($value)) {
                if ($type->getName() === 'object') {
                    return true;
                }

                if ($value instanceof BackedEnum) {
                    if ($type->getName() === 'string') {
                        $value = (string) $value->value;
                        return true;
                    }

                    if (
                        $type->getName() === 'int'
                        and is_int($value->value)
                    ) {
                        $value = $value->value;
                        return true;
                    }
                }
                else if (
                    $value instanceof UnitEnum
                    and $type->getName() === 'string'
                ) {
                    $value = $value->name;
                    return true;
                }
                else if (
                    !$type->isBuiltin()
                    and $value instanceof ($type->getName())
                ) {
                    return true;
                }
            }

            // Converting booleans from scalar types.
            if (!is_bool($value) and $type->getName() === 'bool') {
                if ($is_empty or !is_scalar($value)) {
                    $value = false;
                    return true;
                }

                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                return true;
            }

            // Converting integers from numeric types.
            if (!is_int($value) and $type->getName() === 'int') {
                if ($is_empty or !is_numeric($value)) {
                    $value = 0;
                    return true;
                }

                $value = (int) $value;
                return true;
            }

            // Converting floats from numeric types.
            if (!is_float($value) and $type->getName() === 'float') {
                if ($is_empty or !is_numeric($value)) {
                    $value = 0.0;
                    return true;
                }

                $value = (float) $value;
                return true;
            }

            // Converting strings from scalar types.
            if (!is_string($value) and $type->getName() === 'string') {
                if ($is_empty or !is_scalar($value)) {
                    $value = '';
                    return true;
                }

                $value = (string) $value;
                return true;
            }

            // Converting iterables.
            if (is_iterable($value) and $type->getName() === 'array') {
                $value = iterator_to_array($value);
                return true;
            }

            // Wrap things into arrays.
            if (!is_array($value) and $type->getName() === 'array') {
                $value = [ $value ];
                return true;
            }
        }

        // No match.
        return false;
    }
}
