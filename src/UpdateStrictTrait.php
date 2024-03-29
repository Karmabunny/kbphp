<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use InvalidArgumentException;

/**
 * This modifies the behaviour of a DataObject/Collection so that only
 * properties defined in the class are updated.
 *
 * This extends the behaviour of {@see UpdateTidyTrait}, where it will throw
 * errors if a field is missing instead of silently ignoring it.
 *
 * @package karmabunny\kb
 */
trait UpdateStrictTrait
{
    use PropertiesTrait;

    /**
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        $fields = array_fill_keys(static::getProperties(), true);

        $errors = [];

        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $fields)) {
                $errors[] = $key;
                continue;
            }

            $this->$key = $value;
        }

        // Throw all at once.
        if ($errors) {
            $count = count($errors);
            $errors = array_slice($errors, 0, 25);
            $errors = implode(', ', $errors);

            if ($count > 25) {
                $errors .= ' ...';
            }

            throw new InvalidArgumentException("Unknown fields ({$count}): {$errors}");
        }

        if (method_exists($this, 'applyVirtual')) {
            call_user_func([$this, 'applyVirtual']);
        }
    }
}
