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
 * Checks a value is one of the allowed values
 *
 * @package karmabunny\kb\rules
 */
class InArrayRule extends BaseRule
{

    /** @var array */
    public $allowed = [];


    /** @inheritdoc */
    public function parse(array $ruleset)
    {
        parent::parse($ruleset);

        $this->allowed = $ruleset['allowed'] ?? [];

        if (empty($this->allowed)) {
            throw new InvalidArgumentException('Invalid rule, missing allowed options');
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (empty($this->allowed)) {
            return;
        }

        if (!in_array($value, $this->allowed)) {
            throw new ValidationException('Invalid value');
        }
    }
}
