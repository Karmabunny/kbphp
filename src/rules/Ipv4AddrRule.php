<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks that a value is a valid IPv4 address
 *
 * @package karmabunny\kb\rules
 */
class Ipv4AddrRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!preg_match('/^[0-9]+(?:\.[0-9]+){3}$/', $value)) {
            throw new ValidationException('Invalid IP address');
        }

        $parts = explode('.', $value);

        foreach ($parts as $part) {
            if ($part > 255) {
                throw new ValidationException('Invalid IP address');
            }
        }
    }
}
