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
 * Checks that a date range is valid.
 *
 * @package karmabunny\kb\rules
 */
class DateRangeRule extends BaseRule
{

    public $min = null;

    public $max = null;

    public $ordered = true;


    /** @inheritdoc */
    public function parse(array $ruleset): void
    {
        parent::parse($ruleset);

        $this->min = $ruleset['min'] ?? null;
        $this->max = $ruleset['max'] ?? null;

        if ($ordered = $ruleset['ordered'] ?? null) {
            $this->ordered = $ordered;
        }

        if (count($this->fields) !== 2) {
            throw new InvalidArgumentException('Incorrect number of fields. A date range must only contain two dates: a start and an end date.');
        }
    }


    /** @inheritdoc */
    public function validate($data): void
    {
        if (count($this->fields) != 2) {
            return;
        }

        $values = $this->getFieldValues($data);
        list($date_start, $date_end) = $values;

        $rule = new MysqlDateRule();
        $rule->validateOne($this->fields[0], $date_start);

        $rule = new MysqlTimeRule();
        $rule->validateOne($this->fields[1], $date_end);

        $ts_start = strtotime($date_start);
        $ts_end = strtotime($date_end);

        // Ideally we'd just switch the values around but that isn't possible
        if ($this->ordered and $ts_start > $ts_end) {
            throw new ValidationException("The start date, {$date_start}, cannot be later than the end date {$date_end}");
        }

        if ($this->min) {
            $ts_min = strtotime($this->min);

            if ($ts_start < $ts_min) {
                throw new ValidationException("The start of this date range is outside the minimum of {$this->min}");
            }
        }

        if ($this->max) {
            $ts_max = strtotime($this->max);

            if ($ts_end > $ts_max) {
                throw new ValidationException("The end of this date range is outside the maximum of {$this->max}");
            }
        }
    }
}
