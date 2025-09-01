<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks if a phone number is valid.
 *
 * @package karmabunny\kb\rules
 */
class PhoneRule extends BaseRule
{

    public $digits = 8;


    /** @inheritdoc */
    public function parse(array $ruleset): void
    {
        parent::parse($ruleset);

        if ($digits = $ruleset['digits'] ?? null) {
            $this->digits = $digits;
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        // Allow international numbers starting with + and country code, e.g. +61 for Australia
        $clean = preg_replace('/^\+[0-9]+ */', '', $value);

        // Allow area code in parentheses, e.g. in Australia (08) or Mexico (01 55)
        $clean = preg_replace('/^\(([0-9]+(?: [0-9]+)*)\)/', '$1', $clean);

        // Allow all kinds of different digit separation:
        // space (AU), dash - (US), dot . and slash / (crazy Belgians)
        if (preg_match('#[^\- 0-9/\.]#', $clean)) {
            if (preg_match('#[\+\(\)]#', $clean)) {
                throw new ValidationException("Invalid format");
            }
            throw new ValidationException("Contains invalid characters");
        }

        // Check length meets the minimum requirement
        $len = strlen(preg_replace('/[^0-9]/', '', $value));
        if ($len < $this->digits) {
            throw new ValidationException("Must contain at least {$this->digits} digits");
        }
        if ($len > 15) {
            throw new ValidationException("Cannot contain more than 15 digits");
        }
    }
}
