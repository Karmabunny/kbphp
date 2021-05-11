<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This is a really dumb version of a Collection.
 *
 * Again, it's to encourage stronger typing for blobs of data moving within
 * a system. But here we're stripping all the additional Collection magic.
 *
 * There's no array access, serialization, validations, or magic fields.
 * Just data.
 *
 * @package karmabunny\kb
 */
class DataObject
{
    /**
     * @param iterable $config
     */
    function __construct($config = [])
    {
        $this->update($config);
    }

    /**
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        foreach ($config as $key => $item) {
            $this->$key = $item;
        }
    }
}
