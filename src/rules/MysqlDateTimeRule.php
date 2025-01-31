<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks if a value is a datetime in MySQL format (YYYY-MM-DD HH:MM:SS)
 *
 * @package karmabunny\kb\rules
 */
class MysqlDateTimeRule extends BaseRule
{

    /** @inheritdoc */
    public static function getName(): string
    {
        return 'datetimeMySQL';
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', $value, $matches)) {
            throw new ValidationException('Invalid datedate format');
        }

        $rule = new MysqlDateRule();
        $rule->validateOne($field, $matches[1]);

        $rule = new MysqlTimeRule();
        $rule->validateOne($field, $matches[2]);
    }
}
