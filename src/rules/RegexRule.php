<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use InvalidArgumentException;
use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * Checks that a value matches a regular expression
 *
 * @package karmabunny\kb\rules
 */
class RegexRule extends BaseRule
{

    public $pattern = null;

    public $max = null;

    public $ordered = true;


    /** @inheritdoc */
    public function parse(array $ruleset): void
    {
        parent::parse($ruleset);

        $this->pattern = $ruleset['pattern'] ?? null;

        if (!$this->pattern) {
            throw new InvalidArgumentException('Invalid rule, missing \'pattern\' key');
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!$this->pattern) {
            return;
        }

        if (!preg_match($this->pattern, $value)) {
            throw new ValidationException('Incorrect format');
        }
    }
}
