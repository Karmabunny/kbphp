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
 * Checks the length of a string is within an allowed range
 *
 * @package karmabunny\kb\rules
 */
class LengthRule extends BaseRule
{

    public $min = 0;

    public $max = PHP_INT_MAX;


    /** @inheritdoc */
    public function parse(array $ruleset)
    {
        parent::parse($ruleset);

        $this->min = $ruleset['min'] ?? 0;
        $this->max = $ruleset['max'] ?? PHP_INT_MAX;
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $len = mb_strlen($value);

        if ($len < $this->min) {
            throw new ValidationException("Shorter than minimum allowed length of {$this->min}");
        }

        if ($len > $this->max) {
            throw new ValidationException("Longer than maximum allowed length of {$this->max}");
        }
    }
}
