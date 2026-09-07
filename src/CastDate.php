<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Throwable;

/**
 * Cast dates from valid sources.
 *
 * Timezone behaviours:
 *
 * - 'default' retains the TZ of a given value and only applies the
 *   system TZ if one is absent (strings, numbers).
 *
 * - 'system' sets the TZ to the system TZ, always.
 *
 * - 'utc' sets the TZ to UTC, always.
 *
 * - <iso-name> sets the TZ to the given ISO name, e.g. 'Europe/London'.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CastDate extends Cast
{

    /**
     *
     * @param string $timezone default|system|utc|<iso-name>
     * @return void
     */
    public function __construct(public string $timezone = 'default')
    {
    }


    /** @inheritdoc */
    public function build(mixed $value): mixed
    {
        $property = new ReflectionProperty($this->target, $this->property);
        $type = $property->getType();

        if ($value === null) {
            if (!$type or $type->allowsNull()) {
                return null;
            }

            throw new InvalidArgumentException("Cannot set null on {$this->property}");
        }

        $zone = match ($this->timezone) {
            'default' => null,
            'system' => new DateTimeZone(date_default_timezone_get()),
            'utc' => new DateTimeZone('UTC'),
            default => new DateTimeZone($this->timezone),
        };

        if ($type === null) {
            $class = DateTimeImmutable::class;
        }
        else if ($type instanceof ReflectionNamedType) {
            $class = $type->getName();
        }
        else if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $type) {
                if (
                    $type instanceof ReflectionNamedType
                    and is_subclass_of($type->getName(), DateTimeInterface::class)
                ) {
                    $class = $type->getName();
                    break;
                }
            }
        }

        if (!isset($class) or !is_subclass_of($class, DateTimeInterface::class)) {
            throw new InvalidArgumentException("Invalid date type: {$type->__toString()}");
        }

        /** @var class-string<DateTime|DateTimeImmutable> $class */

        try {
            // Pass-through.
            if ($value instanceof DateTimeInterface) {
                $value = $class::createFromInterface($value);
            }
            // Parse integer/floats as timestamps with microseconds.
            else if (is_numeric($value)) {
                $timestamp = sprintf('%.6f', $value);
                $value = $class::createFromFormat('U.u', $timestamp, $zone);

                if ($value === false) {
                    throw new InvalidArgumentException("Invalid timestamp: {$timestamp}");
                }
            }
            // Classic timey-wimey parsing.
            else if (is_string($value)) {
                $value = new $class($value, $zone);
            }

            if ($zone !== null) {
                $value = $value->setTimezone($zone);
            }

            return $value;
        }
        catch (Throwable $error) {
            if ($this->isNullable()) {
                return null;
            }

            throw $error;
        }
    }
}
