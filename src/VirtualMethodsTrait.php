<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 *
 */
trait VirtualMethodsTrait
{

    /**
     *
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        // TODO Test this.
        if ($value = parent::__get($name)) return $value;

        $name = ucwords($name, '_');
        $name = str_replace('_', '', $name);

        foreach (['', 'get', 'is', 'has'] as $prefix) {
            $function = [$this, $prefix . $name];
            if (is_callable($function)) {
                return $function();
            }
        }

        return null;
    }
}
