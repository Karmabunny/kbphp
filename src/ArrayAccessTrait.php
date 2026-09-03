<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;



/**
 * @mixin \ArrayAccess
 */
trait ArrayAccessTrait
{

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }


    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset ?? null;
    }


    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }


    public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }
}
