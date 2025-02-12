<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks if a value is a time in MySQL format (HH:MM:SS)
 *
 * @package karmabunny\kb\rules
 */
class MysqlTimeRule extends BaseRule
{

    /** @inheritdoc */
    public static function getName(): string
    {
        return 'timeMySQL';
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $value, $matches)) {
            throw new ValidationException('Invalid time format');
        }

        if ($matches[1] < 0 or $matches[1] > 23) {
            throw new ValidationException('Hour is outside of range of 0 to 23');
        }

        if ($matches[2] < 0 or $matches[2] > 59) {
            throw new ValidationException('Minute is outside of range of 0 to 59');
        }

        if ($matches[3] < 0 or $matches[3] > 59) {
            throw new ValidationException('Second is outside of range of 0 to 59');
        }
    }
}
