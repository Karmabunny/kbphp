<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use karmabunny\interfaces\ValidatesInterface;

/**
 * Use '@var' comments to validate object properties.
 *
 * @see DocValidator
 *
 * @mixin ValidatesInterface
 * @package karmabunny\kb
 */
trait DocValidatorTrait {


    /**
     * Validate this scenario (or default).
     *
     * This will throw if invalid or pass silently if valid.
     *
     * @param string|null $scenario null for default.
     *
     * @return void
     * @throws ValidationException
     */
    public function validate(?string $scenario = null): void
    {
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
    public function valid(?string $scenario = null): array|true
    {
        $valid = new DocValidator($this);
        if (!$valid->validate()) {
            return $valid->getErrors();
        }

        return true;
    }
}

