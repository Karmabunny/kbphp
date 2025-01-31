<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks that a value is numeric (integral or decimal)
 *
 * @package karmabunny\kb\rules
 */
class NumericRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!is_numeric($value)) {
            throw new ValidationException('Value must be a number');
        }
    }
}
