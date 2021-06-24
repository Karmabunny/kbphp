<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

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
     * @var array [ item => [errors] ]
     */
    public $errors = [];


    /**
     * Get the validation errors.
     *
     * @return array [ item => [errors] ]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }


    /**
     * Merge errors properly.
     *
     * Whereas array_merge() will clobber messages within duplicate keys.
     *
     * @param array $errors
     * @return static
     */
    public function addErrors(array $errors)
    {
        foreach ($errors as $name => $messages) {
            if (isset($this->errors[$name])) {
                array_push($this->errors[$name], ...$messages);
            }
            else {
                $this->errors[$name] = $messages;
            }
        }

        $this->message = self::getSummary($this->errors);

        return $this;
    }


    /**
     * Create a summary messages from an error set.
     *
     * @param array $errors
     * @return string
     */
    public static function getSummary(array $errors): string
    {
        if (empty($errors)) return '';

        $names = [];
        foreach ($errors as $name => $_) {
            $names[] = "'{$name}'";
        }

        return 'Validation failed for ' . implode(', ', $names);
    }
}
