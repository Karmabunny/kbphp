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
abstract class DataObject
{
    /**
     * @param iterable $config
     */
    function __construct($config = [])
    {
        // This makes things not break. Something about references.
        if (!is_array($config)) {
            $config = iterator_to_array($config);
        }
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

        if (method_exists($this, 'applyVirtual')) {
            call_user_func([$this, 'applyVirtual']);
        }
    }
}
