<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2021 Karmabunny
*/

namespace karmabunny\kb;

use JsonException;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * This implements basic `update()` behaviour for an object.
 *
 * Only fields that are defined as properties will be set. Unknown fields
 * are silently ignored.
 *
 * To raise errors on unknown fields {@see UpdateStrictTrait}.
 *
 * @package karmabunny\kb
 */
trait UpdateTrait
{
    /**
     * Convert a JSON-encoded string to an array where expected
     *
     * @throws JsonException
     */
    protected function convertJsonItem(string $key, mixed &$item): void
    {
        $type = (new ReflectionProperty($this, $key))->getType();
        if (is_array($item) || (!$type instanceof ReflectionNamedType) || $type->getName() !== 'array') {
            return;
        }

        if ($item === '' || $item === null) {
            if ($property_type->allowsNull()) {
                $item = null;
            } else {
                $item = [];
            }
            return;
        }

        // N.B. a MySQL JSON column will always store valid JSON, so
        // Json::decode should never throw an exception, outside of
        // memory/depth constraints
        $item = Json::decode($item);

        // Gracefully handle change from single value to multi-value column
        if (is_scalar($item)) {
            $item = [$item];
        }
    }

    /**
     *
     * @param iterable $config
     * @return void
     * @throws JsonException
     */
    public function update($config)
    {
        foreach ($config as $key => $item) {
            if (!property_exists($this, $key)) {
                continue;
            }

            $this->convertJsonItem($key, $item);
            $this->$key = $item;
        }

        if (method_exists($this, 'applyVirtual')) {
            call_user_func([$this, 'applyVirtual']);
        }
    }
}
