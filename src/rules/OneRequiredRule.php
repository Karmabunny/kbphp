<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;

/**
 * At least one value must be specified (e.g. one of email/phone/mobile).
 *
 * @package karmabunny\kb\rules
 */
class OneRequiredRule extends BaseRule
{
    /** @var string|null */
    public $group;

    public function parse(array $ruleset)
    {
        parent::parse($ruleset);

        $this->group = $ruleset['group'] ?? null;
    }


    /** @inheritdoc */
    public function validate($data)
    {
        $values = $this->getFieldValues($data);

        if (empty($values)) {
            if ($this->group) {
                throw new ValidationException("{$this->group}: At least one of these must be provided");
            }

            throw new ValidationException("At least one of these must be provided");
        }
    }
}
