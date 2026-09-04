<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;

/**
 * Cast an iterable to a colletion of objects.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CastArray extends Cast
{

    /**
     *
     * @param class-string $class
     * @param bool $preserve_keys
     * @return void
     */
    public function __construct(public string $class, public bool $preserve_keys = true)
    {
    }


    /** @inheritdoc */
    public function build(mixed $value): ?array
    {
        if (!is_iterable($value)) {
            return $this->isNullable() ? null : [];
        }

        $objects = [];

        if ($this->preserve_keys) {
            foreach ($value as $key => $item) {
                if (is_a($item, $this->class)) {
                    $objects[$key] = $item;
                }
                else if (is_array($item)) {
                    $objects[$key] = Configure::create($this->class, $item);
                }
            }
        }
        else {
            foreach ($value as $item) {
                if (is_a($item, $this->class)) {
                    $objects[] = $item;
                }
                else if (is_array($item)) {
                    $objects[] = Configure::create($this->class, $item);
                }
            }
        }

        return $objects;
    }
}
