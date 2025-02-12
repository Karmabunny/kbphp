<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks if a value is a date in MySQL format (YYYY-MM-DD)
 *
 * @package karmabunny\kb\rules
 */
class MysqlDateRule extends BaseRule
{

    /** @inheritdoc */
    public static function getName(): string
    {
        return 'dateMySQL';
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value, $matches)) {
            throw new ValidationException('Invalid date format');
        }

        if ($matches[1] < 1900 or $matches[1] > 2100) {
            throw new ValidationException('Year is outside of range of 1900 to 2100');
        }

        if ($matches[2] < 1 or $matches[2] > 12) {
            throw new ValidationException('Month is outside of range of 1 to 12');
        }

        if ($matches[3] < 1 or $matches[3] > 31) {
            throw new ValidationException('Day is outside of range of 1 to 31');
        }
    }
}
