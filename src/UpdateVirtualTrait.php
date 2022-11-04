<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use Closure;

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
     *     // Convert an array of objects.
     *     'sites' => [Sites::class],
     *
     *     // A local converter method.
     *     'other' => [$this, 'someMethod'],
     *
     *     // Some external method.
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
        $virtuals = $this->virtual();

        foreach ($config as $name => $value) {
            $virtual = $virtuals[$name] ?? null;

            // Build objects if present.
            // do this first incase it's callable.
            if (is_object($virtual) and !($virtual instanceof Closure)) {
                $this->{$name} = Configure::configure([$virtual => $value]);
                continue;
            }

            // Mirror behaviour from UpdateVirtualTrait.
            if (is_callable($virtual)) {
                $virtual($value);
                continue;
            }

            // Nested array behaviour.
            if (is_array($virtual) and class_exists($virtual[0] ?? '')) {
                $this->{$name} = [];

                foreach ($value as $key => $item) {
                    $this->{$name}[$key] = Configure::configure([$virtual[0] => $item]);
                }

                continue;
            }
        }
    }


    /**
     * Apply the virtual converters to the all properties.
     *
     * Recommended placements:
     *  - `__clone()`
     *  - `update()`
     *
     * @return void
     */
    protected function applyVirtual()
    {
        // Now run through the virtual stuff.
        $virtual = $this->virtual();

        foreach ($virtual as $key => $fn) {
            $fn($this->$key);
        }
    }
}
