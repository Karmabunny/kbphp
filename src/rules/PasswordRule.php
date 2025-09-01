<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Validate password by length, type of characters
 *
 * @package karmabunny\kb\rules
 */
class PasswordRule extends BaseRule
{


    public $digits = 8;


    /** @inheritdoc */
    public function parse(array $ruleset): void
    {
        parent::parse($ruleset);

        if ($length = $ruleset['length'] ?? null) {
            $this->digits = $length;
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $errors = [];

        if (mb_strlen($value) < $this->digits) {
            $errors[] = "must be at least 8 characters long";
        }

        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = "must contain a lowercase letter";
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = "must contain an uppercase letter";
        }

        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = "must contain a number";
        }

        if (count($errors) > 0) {
            throw (new ValidationException)
                ->addErrors([$field => $errors]);
        }
    }
}
