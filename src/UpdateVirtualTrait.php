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
 * Implement the {@see UpdateVirtualTrait} to enable this for DataObject types.
 *
 * @package karmabunny\kb
 */
trait UpdateVirtualTrait
{

    /**
     * A list of converter functions to apply to the data before saving.
     *
     * Example:
     * ```php
     * [
     *     'destinations' => [$this, 'setDestinations'],
     *     'some_string_i_guess' => 'trim',
     * ]
     * ```
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
     * @return void
     */
    public function setVirtual($config)
    {
        $virtual = $this->virtual();

        foreach ($config as $key => $value) {
            $fn = $virtual[$key] ?? null;
            if (!$fn) continue;
            $fn($value);
        }
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
