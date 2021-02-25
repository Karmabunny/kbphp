<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This class can validate.
 *
 * There are two provided implementations if you don't want to write your own.
 *
 * {@see DocValidatorTrait}
 * Uses '@var' doc comments to infer the required and intended types.
 *
 * {@see RulesValidatorTrait}
 * Uses the rules() method to declare validator function.
 * Default validity methods are provided by {@see Validity}.
 *
 * Combining validators should also be possible using the respective Validator
 * classes - {@see DocValidator} and {@see RulesValidator}.
 *
 * Something like:
 *
 * public function validate() {
 *     $docs = new DocsValidator($this);
 *     $rules = new RulesValidator($this);
 *     if (!$docs->validate() or !$rules->validate()) {
 *         throw (new ValidationException)
 *              ->addErrors($docs->getErrors())
 *              ->addErrors($docs->getErrors());
 *     }
 * }
 *
 * @package karmabunny\kb
 */
interface Validates {

    /**
     * Perform validation.
     *
     * @throws ValidationException
     */
    public function validate(string $scenario = null);

}
