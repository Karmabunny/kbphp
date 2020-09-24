<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Validator errors.
 *
 * @package karmabunny/kb
 */
class ValidationException extends \Exception {

    /**
     * Keyed string => string[].
     *
     * @var array
     */
    public $errors = [];


    /**
     * Construct an appropriate validation message.
     *
     * @param array $errors Keyed string => string[].
     */
    public function __construct($errors)
    {
        $this->errors = $errors;

        if (count($errors) == 1) {
            $name = key($errors);
            $this->message = "Validation failed for '{$name}'";

            $value = $errors[$name];
            if (count($value) == 1) {
                $this->message .= ', ' . current($errors[$name]);
            }
        }
        else {
            $names = array_map(function($name) {
                return "'{$name}'";
            }, array_keys($errors));

            $this->message = 'Validation failed for ' . implode(', ', $names);
        }
    }


    /**
     * Merge errors properly.
     *
     * Whereas array_merge() will clobber messages within duplicate keys.
     *
     * @param array ...$arrays
     * @return array
     */
    public static function mergeErrors(...$arrays): array
    {
        $all = [];

        foreach ($arrays as $errors) {
            foreach ($errors as $name => $messages) {
                if (isset($all[$name])) {
                    array_push($all[$name], ...$messages);
                }
                else {
                    $all[$name] = $messages;
                }
            }
        }

        return $all;
    }
}
