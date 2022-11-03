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
 * @package karmabunny\kb
 */
trait UpdateVirtualTrait
{
    use HooksTrait;


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
     * Apply the virtual converters to the all properties.
     *
     * @return void
     * @hook update
     */
    protected function applyVirtual($config)
    {
        // Now run through the virtual stuff.
        $virtual = $this->virtual();

        foreach ($virtual as $key => $fn) {
            $value = $config[$key] ?? null;
            $fn($value);
        }
    }
}
