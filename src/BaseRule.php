<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use InvalidArgumentException;

/**
 * A base helper class for rules.
 *
 * Use in `rules()` like so:
 *
 * ```
 * [
 *    [ MyRule::class => ['field1', 'field2'] ],
 *    [ MyRule::class => ['field1', 'field2', 'arg1' => 123] ],
 *    [ MyOtherRule::class => ['field3', 'field4'] ],
 * ]
 *
 * @package karmabunny\kb\rules
 */
abstract class BaseRule implements RuleInterface
{

    /** @var string[] */
    public $fields = [];


    /** @inheritdoc */
    public function parse(array $ruleset)
    {
        $this->fields = [];

        foreach ($ruleset as $key => $field) {
            if (is_numeric($key) and is_string($field)) {
                $this->fields[] = $field;
            }
        }

        if (empty($this->fields)) {
            $name = static::getName();
            throw new InvalidArgumentException("Invalid rule specification: {$name}, missing fields");
        }
    }


    /** @inheritdoc */
    public static function getName(): string
    {
        $name = str_replace('\\', '/', static::class);
        $name = basename($name);
        $name = lcfirst($name);
        $name = preg_replace('/Rule$/', '', $name);
        return $name;
    }


    /** @inheritdoc */
    public function fields(): array
    {
        return $this->fields;
    }


    /** @inheritdoc */
    public function validate($data)
    {
        if (empty($this->fields)) {
            return;
        }

        $error = new ValidationException();

        foreach ($this->fields as $field) {
            if (self::isEmpty($data, $field)) {
                continue;
            }

            try {
                $this->validateOne($field, $data[$field]);
            }
            catch (ValidationException $exception) {
                if (
                    ($errors = $exception->getErrors())
                    and isset($errors[$field])
                ) {
                    $error->addErrors([$field => $errors[$field]]);
                }
                else {
                    $error->errors[$field][] = $exception->getMessage();
                }
            }
        }

        if (!empty($error->errors)) {
            throw $error;
        }
    }


    /**
     * Validate a single value against this rule.
     *
     * In the base implementation of `validate()` this is called for each field.
     *
     * Multi-check rules will likely skip this method entirely.
     *
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function validateOne(string $field, $value)
    {
    }


    /**
     * Get a list of values that match this rule's fields.
     *
     * @param array|ArrayAccess $data
     * @return array
     */
    public function getFieldValues($data): array
    {
        $values = [];

        foreach ($this->fields as $field) {
            if (self::isEmpty($data, $field)) {
                continue;
            }

            $values[] = $data[$field];
        }

        return $values;
    }


    /**
     * Is this data field empty?
     *
     * Note, this considers a numeric 'zero' as non-empty.
     *
     * @param mixed $data
     * @param string $field
     * @return bool
     */
    public static function isEmpty($data, string $field): bool
    {
        $value = $data[$field] ?? null;

        if ($value === null) {
            return true;
        }

        if (is_array($value) and count($value) == 0) {
            return true;
        }

        if (!is_numeric($value) and $value == '') {
            return true;
        }

        return false;
    }

}
