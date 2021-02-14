<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;


trait ArrayAccessTrait
{
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }


    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }


    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }


    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}
