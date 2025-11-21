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
class OneRequiredRule extends BaseRule
{

    /** @inheritdoc */
    public function validate($data)
    {
        $values = $this->getFieldValues($data);

        foreach ($values as $v) {
            if (is_array($v) and count($v) > 0) {
                return;
            } else if ($v != '') {
                return;
            }
        }

        throw new ValidationException("At least one of these must be provided");
    }
}
