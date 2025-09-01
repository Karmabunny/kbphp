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
 * Checks that a value is numeric (integral or decimal) and within a given inclusive range.
 *
 * @package karmabunny\kb\rules
 */
class RangeRule extends BaseRule
{

    public $min = null;

    public $max = null;


    /** @inheritdoc */
    public function parse(array $ruleset): void
    {
        parent::parse($ruleset);

        $between = $ruleset['between'] ?? [];

        if (empty($between)) {
            throw new InvalidArgumentException('Invalid rule, missing \'between\' key');
        }

        if (count($between) !== 2) {
            throw new InvalidArgumentException('Invalid rule, \'between\' needs exactly two values');
        }

        $this->min = $between[0] ?? null;
        $this->max = $between[1] ?? null;
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!is_numeric($value)) {
            throw new ValidationException('Value must be a number');
        }

        if ($value < $this->min or $value > $this->max) {
            throw new ValidationException("Value must be no less than {$this->min} and no greater than {$this->max}");
        }
    }
}
