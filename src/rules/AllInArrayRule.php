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
 * Checks each value of an array is one of the allowed values
 *
 * @package karmabunny\kb\rules
 */
class AllInArrayRule extends BaseRule
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

        if (!is_array($value)) {
            throw new ValidationException('Invalid value');
        }

        if (count(array_diff($value, $this->allowed)) > 0) {
            throw new ValidationException('Invalid value');
        }
    }
}
