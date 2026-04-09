<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This modifies the behaviour of a DataObject/Collection for updating complex
 * properties, such as arrays and objects.
 *
 * Implement the {@see UpdateVirtualInterface} to enable this for DataObject types.
 *
 * @package karmabunny\kb
 */
trait UpdateVirtualTrait
{

    /**
     * A list of converter functions or target objects.
     *
     * Example:
     * ```php
     * [
     *     // Convert a single object.
     *     'user' => User::class,
     *
     *     // Some external method.
     *     'some_string_i_guess' => 'trim',
     * ]
     * ```
     *
     * Fields specified here are included in the returned 'update set' from
     * the `setVirtual()` method.
     *
     * Returning `false` from a method will prevent the field being included
     * in the update set.
     *
     * @return callable[]
     */
    public function virtual(): array
    {
        return [];
    }


    /**
     * Apply virtual properties.
     *
     * @param iterable $config
     * @return string[] set of updated fields
     */
    public function setVirtual($config): array
    {
        $virtuals = $this->virtual();
        $updated = [];

        foreach ($virtuals as $name => $virtual) {

            if (!is_callable($virtual)) {
                continue;
            }

            if (!array_key_exists($name, $config)) {
                continue;
            }

            $ok = $virtual($config[$name]);

            if ($ok !== false) {
                $updated[] = $name;
            }
        }

        return $updated;
    }


    /**
     * Apply the virtual converters to the all properties.
     *
     * @deprecated use setVirtual()
     * @return void
     */
    protected function applyVirtual()
    {
        $virtual = $this->virtual();

        foreach ($virtual as $key => $fn) {
            $fn($this->$key);
        }
    }
}
