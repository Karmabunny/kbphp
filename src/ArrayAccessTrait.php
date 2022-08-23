<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ReturnTypeWillChange;


trait ArrayAccessTrait
{

    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }


    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }


    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }


    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}
