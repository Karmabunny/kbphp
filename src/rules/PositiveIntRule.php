<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks if a value is a positive integer
 *
 * @package karmabunny\kb\rules
 */
class PositiveIntRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (preg_match('/[^0-9]/', $value)) {
            throw new ValidationException("Value must be a whole number that is greater than zero");
        }

        $int = (int) $value;
        if ($int <= 0) {
            throw new ValidationException("Value must be greater than zero");
        }
    }
}
