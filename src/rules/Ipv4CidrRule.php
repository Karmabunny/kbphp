<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks that a value is a valid IPv4 CIDR block
 *
 * @package karmabunny\kb\rules
 */
class Ipv4CidrRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (strpos($value, '/') === false) {
            throw new ValidationException('Invalid CIDR block');
        }

        list($ip, $mask) = explode('/', $value, 2);

        $rule = new Ipv4AddrRule();
        $rule->validateOne($field, $ip);

        if (!preg_match('/^[0-9]{1,2}$/', $mask)) {
            throw new ValidationException('Invalid network mask');
        }
        $mask = (int) $mask;
        if ($mask > 32) {
            throw new ValidationException('Invalid network mask');
        }
    }
}
