<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * All field values must match (e.g. password1 and password2 must match)
 *
 * @package karmabunny\kb\rules
 */
class AllMatchRule extends BaseRule
{

    /** @inheritdoc */
    public function validate($data)
    {
        $values = $this->getFieldValues($data);
        $unique = array_unique($values);

        if (count($unique) > 1) {
            throw new ValidationException("Provided values do not match");
        }
    }
}
