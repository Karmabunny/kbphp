<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use Error;
use InvalidArgumentException;
use ReflectionProperty;

/**
 * Attach this to a property to convert it automatically to the target class.
 *
 * The object must implement the {@see AttributeVirtualTrait} and use
 * the `applyVirtual()` helper to invoke the virtual methods.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class VirtualObject extends VirtualPropertyBase
{

    const MODE_SINGLE = 1;
    const MODE_ARRAY = 2;
    const MODE_PRESERVE_KEYS = 6;


    /**
     * @var string
     */
    public $class;


    /** @var int */
    public $mode = self::MODE_SINGLE;


    /**
     *
     * @param string $method
     * @param int|bool|string $mode
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $class, $mode = self::MODE_SINGLE)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Target class does not exist: {$class}");
        }

        $this->class = $class;
        $this->mode = self::parseMode($mode);
    }


    /** @inheritdoc */
    public function apply(object $target, mixed $value)
    {
        if (!($this->reflect instanceof ReflectionProperty)) {
            throw new Error('VirtualProperty must be parsed from an object');
        }

        $this->reflect->setAccessible(true);

        if ($this->mode & self::MODE_SINGLE) {
            $value = Configure::configure([ $this->class => $value ]);
            $this->reflect->setValue($target, $value);
        }
        else {
            $items = [];

            foreach ($value as $key => $item) {
                $item = Configure::configure([ $this->class => $item ]);

                if ($this->mode & self::MODE_PRESERVE_KEYS) {
                    $items[$key] = $item;
                } else {
                    $items[] = $item;
                }
            }

            $this->reflect->setValue($target, $items);
        }
    }


    /**
     *
     * @param int|bool|string $mode
     * @return int
     */
    public static function parseMode($mode)
    {
        if (is_string($mode)) {
            $mode = strtolower($mode);

            switch ($mode) {
                case 'array':
                    return self::MODE_ARRAY;

                case 'preserve_keys':
                    return self::MODE_PRESERVE_KEYS;

                case 'single':
                    return self::MODE_SINGLE;

                // default fallthrough.
            }
        }
        else if (is_bool($mode)) {
            return $mode ? self::MODE_PRESERVE_KEYS : self::MODE_SINGLE;
        }
        else if (is_int($mode)) {
            return $mode;
        }

        throw new InvalidArgumentException("Invalid mode: {$mode}");
    }
}
