<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * Validator errors.
 *
 * @package karmabunny\kb
 */
class ValidationException extends Exception implements
    Arrayable,
    JsonSerializable
{

    /**
     * Keyed string => string[].
     *
     * @var array[] [ item => [errors] ]
     */
    public $errors = [];


    /**
     * Alternate (human-readable) names for error items.
     *
     * @var string[] [ item => label ]
     */
    public $labels = [];


    /**
     * Get the validation errors.
     *
     * @return array[] [ item => [errors] ]
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
     * Field labels make error messages a little friendlier
     *
     * @param array $labels Field labels
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }


    /**
     * Set the label for a single field
     *
     * @param string $field The field to set
     * @param string $label The label to set on the field
     */
    public function setFieldLabel($field, $label)
    {
        $this->labels[$field] = $label;
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


    /** @inheritdoc */
    public function toArray(): array
    {
        $errors = [];

        foreach ($this->errors as $field => $msgs) {
            $key = $this->labels[$field] ?? $field;
            $errors[$key] = $msgs;
        }

        $out = [];

        foreach ($errors as $label => $msgs) {
            $out[$label] = implode('. ', $msgs);
        }

        return $out;
    }


    /** @inheritdoc */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
