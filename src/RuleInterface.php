<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use InvalidArgumentException;

/**
 * Base interface for validation rules with {@see RulesClassValidator}.
 *
 * @package karmabunny\kb\rules
 */
interface RuleInterface
{

    /**
     * The shorthand name for this rule.
     *
     * @return string
     */
    public static function getName(): string;


    /**
     * Parse a ruleset.
     *
     * This typically specifies the fields to validate and any configurable options.
     *
     * @param array $ruleset
     * @return void
     * @throws InvalidArgumentException
     */
    public function parse(array $ruleset);


    /**
     * What fields are registered for this rule?
     *
     * @return string[]
     */
    public function fields(): array;


    /**
     * Validate data against this rule.
     *
     * The `ValidationException` will specify each field error from
     * the `getErrors()` helper. Otherwise the exception message will be
     * copied into each field of the rule.
     *
     * This is the key distinction between single and multi-check rules.
     *
     * @param array|ArrayAccess $data
     * @return void
     * @throws ValidationException
     */
    public function validate($data);
}
