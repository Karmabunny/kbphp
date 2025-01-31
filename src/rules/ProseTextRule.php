<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks whether a string is made up of the kinds of characters that make up prose
 *
 * Allowed: letters, numbers, space, punctuation
 * Allowed punctuation:
 *    ' " / ! ? @ # $ % & ( ) - : ; . ,
 *
 * @package karmabunny\kb\rules
 */
class ProseTextRule extends BaseRule
{


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        // pL = letters, pN = numbers
        if (preg_match('/[^-\pL\pN \'"\/!?@#$%&():;.,]/u', (string) $value)) {
            throw new ValidationException('Non prose characters found');
        }
    }
}
