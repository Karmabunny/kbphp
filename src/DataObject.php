<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * The simplest object.
 *
 * It's to encourage stronger typing for blobs of data moving within a system.
 * This particular base object has no additional magics.
 *
 * There's no array access, serialization, validations, or magic fields.
 * Just data.
 *
 * For more a magical base object {@see Collection}.
 *
 * @package karmabunny\kb
 */
abstract class DataObject
{
    use UpdateTrait;

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
}
