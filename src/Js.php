<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

/**
 * JS helpers.
 *
 * @package karmabunny\kb
 */
class Js
{

    /**
     * Encode an array to a JavaScript expression.
     *
     * This processes {@see JsExpression} objects.
     *
     * @param array $data
     * @param bool $freeze
     * @return string
     * @throws \JsonException
     */
    public static function encode(array $data, bool $freeze = true)
    {
        $expressions = [];

        array_walk_recursive($data, function(&$value) use (&$expressions) {
            if ($value instanceof JsExpression) {
                $id = '!([{' . uniqid('js_') . '}])!';
                $expressions["\"{$id}\""] = $value->render();
                $value = $id;
            }
        });

        $json = Json::encode($data);

        if ($expressions) {
            $json = strtr($json, $expressions);
        }

        if ($freeze) {
            $json = "Object.freeze({$json})";
        }

        return $json;
    }
}
