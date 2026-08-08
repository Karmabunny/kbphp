<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;

/**
 * Provide support for rules validation with inline (PHP 8) attributes.
 *
 * @see RulesValidator
 * @see Rule
 *
 * @package karmabunny\kb
 */
trait AttributeValidatorTrait
{

    /**
     * Validate this scenario (or default).
     *
     * This will throw if invalid or pass silently if valid.
     *
     * @param string|null $scenario null for default.
     * @return void
     * @throws Exception
     * @throws ValidationException
     */
    public function validate(string $scenario = null) {
        $errors = $this->valid($scenario);
        if ($errors !== true) {
            throw (new ValidationException)->addErrors($errors);
        }
    }


    /**
     * Validate this scenario (or default).
     *
     * @param string|null $scenario null for default.
     * @return array|true True if valid, errors array if invalid.
     * @throws Exception
     */
    public function valid(string $scenario = null)
    {
        $valid = new AttributesValidator($this, $scenario);

        if (!$valid->validate()) {
            return $valid->getErrors();
        }
        return true;
    }
}
