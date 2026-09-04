<?php
namespace karmabunny\kb;

use karmabunny\interfaces\LogSourceInterface;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;

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
     * @param class-string $class
     * @return void
     */
    public function __construct(public string $class)
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
            $property = new ReflectionProperty($this->class, $field);
        }
        catch (ReflectionException $error) {
            $this->log($error, Log::LEVEL_WARNING, static::class);
            return false;
        }

        // See if there's a cast attribute first.
        if ($cast = Cast::find($this->class, $property)) {
            try {
                $value = $cast->build($value);
                return true;
            }
            catch (Throwable $error) {
                $this->log($error, Log::LEVEL_ERROR, static::class);
                return false;
            }
        }

        $type = $property->getType();

        // Without type information anything goes.
        if (!$type instanceof ReflectionNamedType) {
            return true;
        }

        // Explicitly anything goes.
        if ($type->getName() === 'mixed') {
            return true;
        }

        if (get_debug_type($value) === $type->getName()) {
            return true;
        }

        if (is_object($value)) {
            if ($type->getName() === 'object') {
                return true;
            }

            if (is_a($value, $type->getName())) {
                return true;
            }
        }

        // Strict empty, not PHP empty.
        $is_empty = ($value === '' or $value === null);

        if ($type->allowsNull() and $is_empty) {
            $value = null;
            return true;
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

        // No match.
        return false;
    }
}
