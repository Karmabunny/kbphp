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
     *
     * @param iterable $config
     * @return void
     */
    public function update($config)
    {
        // TODO Should probably just let this throw an error here.
        if (!method_exists(parent::class, 'update')) return;

        // Run the regular update first.
        parent::update($config);

        // Now run through the virtual stuff.
        $virtual = $this->virtual();

        foreach ($config as $key => $item) {
            $setter = $virtual[$key] ?? null;
            if (!$setter) continue;
            $setter($item);
        }
    }
}
