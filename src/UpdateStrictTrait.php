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
        $fields = static::getPropertyTypes();

        $errors = [];

        foreach ($config as $key => $value) {
            $type = $fields[$key] ?? null;

            // Check field exists.
            if (!$type) {
                $errors[] = $key;
                continue;
            }

            // Check type matches, but these don't emit an error. Otherwise
            // hooks wouldn't trigger properly.
            // Only occurs on PHP 7.4+.
            if (
                class_exists($type)
                and !is_a($value, $type)
            ) {
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

        if (method_exists($this, '_hook')) {
            call_user_func([$this, '_hook'], __FUNCTION__, $config);
        }
    }
}
