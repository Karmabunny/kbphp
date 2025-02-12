<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks that a value is binary; either a '1' or a '0'.
 *
 * @package karmabunny\kb\rules
 */
class BinaryRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if ($value !== '1' and $value !== 1 and $value !== '0' and $value !== 0) {
            throw new ValidationException('Value must be a "1" or "0"');
        }
    }
}
