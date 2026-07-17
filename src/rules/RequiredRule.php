<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use ArrayAccess;
use ArrayObject;
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
    public function validate(array|object $data): void
    {
        if (empty($this->fields)) {
            return;
        }

        if (is_object($data) and !$data instanceof ArrayAccess) {
            $data = new ArrayObject($data, ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);
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
