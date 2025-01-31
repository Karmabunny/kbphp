<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This class can validate.
 *
 * There are three implementations if you don't want to write your own.
 *
 * {@see DocValidatorTrait}
 * - Uses '@var' doc comments to infer the required and intended types.
 *
 * {@see RulesValidatorTrait} with {@see RulesStaticValidator} (default)
 * - Uses the rules() method to declare validator rulesets.
 * - Default validity methods are provided by {@see Validity}.
 *
 * {@see RulesValidatorTrait} with {@see RulesClassValidator}
 * - Uses the rules() method to declare validator rulesets.
 * - Can load custom {@see RuleInterface} classes.
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
