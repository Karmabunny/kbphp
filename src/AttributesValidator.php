<?php

/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;
use ReflectionProperty;

/**
 * Rules based validation processor.
 *
 * @package karmabunny\kb
 */
class AttributesValidator implements Validator
{

    /** @var object */
    public $target;

    /** @var string|null */
    public $scenario = null;

    /** @var string */
    public $validity = Validity::class;

    /** @var array */
    protected $errors = [];


    /**
     *
     * @param object $target
     * @param string|null $scenario
     */
    public function __construct(object $target, string $scenario = null)
    {
        $this->target = $target;
        $this->scenario = $scenario;
    }


    /**
     * Set the validity helper.
     *
     * @param string $class
     */
    public function setValidity(string $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Invalid validity class: {$class}");
        }

        $this->validity = $class;
    }


    /** @inheritdoc */
    public function validate(): bool
    {
        $rules = Rule::parse($this->target);

        // print_r($rules); die;

        foreach ($rules as $rule) {
            // Not entirely necessary, but it's a nice sanity check and keeps
            // the static analysis happy.
            if (!($rule->reflect instanceof ReflectionProperty)) {
                continue;
            }

            $this->process($rule);
        }

        return empty($this->errors);
    }


    /**
     *
     * @param string $name property
     * @param Rule $rule
     * @return void
     * @throws InvalidArgumentException
     */
    protected function process(Rule $rule)
    {
        // Again, quick assertion to ensure consistency and keep the static
        // analysis out of my hair.
        if (!($rule->reflect instanceof ReflectionProperty)) {
            throw new \Error('Rule parsing failed, missing reflection');
        }

        $name = $rule->reflect->getName();
        $value = $rule->reflect->getValue($this->target);

        try {
            $empty = (
                $value === null
                or RulesValidator::isEmpty($value)
            );

            // Special handling for this one.
            if ($rule->name === 'required') {
                if ($empty) {
                    throw new RequiredFieldException('This field is required');
                }

                // Otherwise fine.
                return null;
            }

            // Skip validation if empty/null.
            if ($empty) {
                return null;
            }

            // Check the local namespace first.
            if (method_exists($this->target, $rule->name)) {
                $func = [$this->target, $rule->name];
            }
            // Or use the validity helper.
            else {
                $func = [$this->validity, $rule->name];
            }

            // Give-er a check.
            if (!is_callable($func)) {
                throw new InvalidArgumentException("Invalid rule: {$rule->name}");
            }

            // Call it!
            return $func($value, ...$rule->args);
        }
        catch (RequiredFieldException $exception) {
            $this->errors[$name]['required'] = $exception->getMessage();
        }
        catch (ValidationException $exception) {
            $this->errors[$name][] = $exception->getMessage();
        }
    }


    /** @inheritdoc */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }


    /** @inheritdoc */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
