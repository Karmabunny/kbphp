<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Validate email, commonly used characters only
 *
 * This checks for only one '@' and _at least_ one 'dot' in the domain.
 * This means `test@localhost` will not pass. This is intentional.
 *
 * @package karmabunny\kb\rules
 */
class EmailRule extends BaseRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $regex = '/^[^@]+@[^@.]+\.[^@]+$/iD';

        if (!preg_match($regex, $value)) {
            throw new ValidationException('Invalid email address');
        }

        $regex = '/[@.][@.]/iD';

        if (preg_match($regex, $value)) {
            throw new ValidationException('Invalid email address');
        }
    }
}
