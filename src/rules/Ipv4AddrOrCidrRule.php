<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;

/**
 * Checks that a value is a valid IPv4 address or CIDR block
 *
 * @package karmabunny\kb\rules
 */
class Ipv4AddrOrCidrRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (strpos($value, '/') === false) {
            $rule = new Ipv4AddrRule();
        }
        else {
            $rule = new Ipv4CidrRule();
        }

        $rule->validateOne($field, $value);
    }
}
