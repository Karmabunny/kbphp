<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

/**
 * This modifies the behaviour of a DataObject/Collection so that only
 * properties defined in the class are updated.
 *
 * The default implementation will create _new_ fields that aren't typed. This
 * trait will only update fields that are explicitly defined. Unknown fields
 * are silently ignored.
 *
 * For a more aggressive approach {@see UpdateStrictTrait}.
 *
 * @package karmabunny\kb
 */
trait UpdateTidyTrait
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

        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $fields)) continue;
            $this->$key = $value;
        }

        if (method_exists($this, 'applyVirtual')) {
            call_user_func([$this, 'applyVirtual']);
        }
    }
}
