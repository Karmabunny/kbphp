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
 * DocValidatorTrait
 * Uses '@var' doc comments to infer the required and intended types.
 *
 * RulesValidatorTrait
 * This uses the rules() method to declare validator function.
 *
 * @package karmabunny/kb
 */
interface Validates {

    /**
     * Perform validation.
     *
     * @throws ValidationException
     */
    public function validate();

}
