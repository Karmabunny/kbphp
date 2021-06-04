<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\Errors;

use Exception;

/**
 * Validator errors.
 *
 * @package karmabunny\kb
 */
class ValidationException extends Exception
{

    /**
     * Keyed string => string[].
     *
     * @var array
     */
    public $errors = [];


    /**
     * Merge errors properly.
     *
     * Whereas array_merge() will clobber messages within duplicate keys.
     *
     * @param array $errors
     * @return static
     */
    public function addErrors($errors)
    {
        foreach ($errors as $name => $messages) {
            if (isset($this->errors[$name])) {
                array_push($this->errors[$name], ...$messages);
            }
            else {
                $this->errors[$name] = $messages;
            }
        }

        $names = array_map(function($name) {
            return "'{$name}'";
        }, array_keys($this->errors));

        $this->message = 'Validation failed for ' . implode(', ', $names);

        return $this;
    }
}
