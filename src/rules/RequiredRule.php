<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * A special rule for required validations.
 *
 * This isn't executed like other rules and is executed immediately.
 *
 * @package karmabunny\kb\rules
 */
class RequiredRule extends BaseRule
{

    /** @inheritdoc */
    public function validate($data): void
    {
        if (empty($this->fields)) {
            return;
        }

        $error = new ValidationException();

        foreach ($this->fields as $field) {
            if (self::isEmpty($data, $field)) {
                $error->errors[$field]['required'] = 'This field is required';
            }
        }

        if (!empty($error->errors)) {
            throw $error;
        }
    }
}
