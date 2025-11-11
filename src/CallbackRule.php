<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;

/**
 * This wraps a inline function to serve as a validator.
 *
 * The function only needs to raise a {@see ValidationException} if the given
 * value is not valid.
 *
 * @package karmabunny\kb
 */
class CallbackRule extends BaseRule
{

    /** @var callable|null */
    public $callable;

    /** @var array */
    public $args = [];

    public $multi = false;


    /** @inheritdoc */
    public function parse(array $ruleset): void
    {
        parent::parse($ruleset);

        if (!is_callable($ruleset['func'] ?? null)) {
            throw new InvalidArgumentException('Invalid rule');
        }

        $this->callable = $ruleset['func'];
        $this->args = $ruleset['args'] ?? [];
        $this->multi = $ruleset['multi'] ?? false;
    }


    /** @inheritdoc */
    public function validate($data): void
    {
        if (!$this->callable or empty($this->fields)) {
            return;
        }

        if ($this->multi) {
            $values = $this->getFieldValues($data);
            ($this->callable)($values, ...$this->args);
        }
        else {
            parent::validate($data);
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!$this->callable) {
            return;
        }

        ($this->callable)($value, ...$this->args);
    }
}
