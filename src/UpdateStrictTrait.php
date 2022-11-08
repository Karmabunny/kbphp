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
     * Update the object.
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        $fields = static::getPropertyTypes();

        $virtual = [];
        $errors = [];

        // Apply virtual properties.
        if ($this instanceof UpdateVirtualInterface) {
            $virtual = $this->setVirtual($config);
            $virtual = array_fill_keys($virtual, true);
        }

        foreach ($config as $key => $value) {
            // Skip virtual fields.
            if (isset($virtual[$key])) continue;

            // Check field exists.
            $type = $fields[$key] ?? null;
            if (!$type) {
                $errors[] = $key;
                continue;
            }

            // Check type matches.
            // Only occurs on PHP 7.4+.
            if (
                is_object($value)
                and class_exists($type)
                and !is_a($value, $type)
            ) {
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

            throw new InvalidArgumentException("Unknown or invalid fields ({$count}): {$errors}");
        }
    }
}
