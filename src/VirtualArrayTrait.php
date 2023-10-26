<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use ReturnTypeWillChange;

/**
 *
 */
trait VirtualArrayTrait
{

    /**
     * Virtual fields, in the same format as {@see Arrayable}.
     *
     * @return array
     */
    public abstract function fields(): array;


    #[ReturnTypeWillChange]
    public function offsetExists($offset)
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


    #[ReturnTypeWillChange]
    public function offsetGet($offset)
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
