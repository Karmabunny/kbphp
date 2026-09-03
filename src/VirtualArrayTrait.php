<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;

/**
 *
 * @mixin ArrayAccess
 */
trait VirtualArrayTrait
{

    /**
     * Virtual fields, in the same format as {@see Arrayable}.
     *
     * @return array
     */
    public abstract function fields(): array;


    /** @inheritdoc */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_numeric($offset)) {
            $fields = $this->fields();
            $item = $fields[$offset] ?? null;
            return (bool) $item;
        }

        if (parent::class instanceof ArrayAccess) {
            return parent::offsetExists($offset);
        }

        return false;
    }


    /** @inheritdoc */
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_numeric($offset)) {
            $fields = $this->fields();
            $item = $fields[$offset] ?? null;

            if (is_callable($item)) {
                return $item();
            }
        }

        if (parent::class instanceof ArrayAccess) {
            return parent::offsetGet($offset);
        }

        return null;
    }
}
