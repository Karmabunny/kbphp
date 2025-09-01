<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use ArrayObject;
use InvalidArgumentException;
use karmabunny\interfaces\RuleInterface;
use karmabunny\interfaces\RulesValidatorInterface;
use karmabunny\kb\rules\RequiredRule;

/**
 * Rules based validation processor.
 *
 * This loads a set of default {@see RuleInterface} rules from the `config/rules.php` file.
 *
 * @package karmabunny\kb
 */
class RulesClassValidator implements RulesValidatorInterface
{

    /** @var array|object */
    protected $data;

    /**
     * Available rules, as installed by setValidators().
     *
     * @var RuleInterface[]
     */
    protected $validators = [];

    /**
     * Active rules, a subset of the validators as determined by setRules().
     *
     * @var RuleInterface[]
     */
    protected $rules = [];

    /**
     * A copy of the original rulesets, used for reparsing rules.
     *
     * @var array
     */
    protected $original_rules = [];

    /** @var array */
    protected $errors = [];


    /**
     * @param array|object $data Data to validate
     */
    public function __construct($data)
    {
        $validators = require __DIR__ . '/config/rules.php';
        $this->setValidators($validators);
        $this->setData($data);
    }


    /**
     * Update the data to validate
     *
     * @param array|object $data Data to validate
     */
    public function setData($data)
    {
        if (is_array($data) or $data instanceof ArrayAccess) {
            $this->data = $data;
        }
        else {
            // An object with no ArrayAccess, so we're wrapping it up!
            // It's easier to just use a Collection - so please do.
            $this->data = new ArrayObject($data, ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);
        }
    }


    /**
     * Set the value for a single data field
     *
     * @param string $field The field to set
     * @param mixed $value The value to set on the field
     */
    public function setFieldValue($field, $value)
    {
        $this->data[$field] = $value;
    }


    /**
     *
     * @param array $validators
     * @return void
     * @throws InvalidArgumentException
     */
    public function setValidators(array $validators)
    {
        $this->validators = [];

        foreach ($validators as $name => $validator) {
            $this->addValidator($validator, is_string($name) ? $name : null);
        }

        $required = $this->validators['required'] ?? null;

        if (!$required instanceof RequiredRule) {
            $this->validators['required'] = new RequiredRule();
        }

        if ($this->rules) {
            $this->refreshRules();
        }
    }


    /**
     *
     * @param array|class-string|RuleInterface $validator
     * @param null|string $name override
     * @return void
     * @throws InvalidArgumentException
     */
    public function addValidator($validator, ?string $name = null)
    {
        /** @var RuleInterface $validator */
        $validator = Configure::configure($validator, RuleInterface::class);
        $name = $name ?? $validator::getName();
        $this->validators[$name] = $validator;
    }


    /**
     *
     * @param string $name
     * @param array $ruleset
     * @return RuleInterface
     * @throws InvalidArgumentException
     */
    public function parseRule(string $name, array $ruleset): RuleInterface
    {
        // Custom rule.
        // :: class => [ruleset]
        if (is_subclass_of($name, RuleInterface::class)) {
            /** @var RuleInterface $rule */
            $rule = Configure::instance($name);
            $rule->parse($ruleset);
            return $rule;
        }

        // Standard validator type.
        // :: name => [ruleset]
        if ($validator = $this->validators[$name] ?? null) {
            $rule = clone $validator;
            $rule->parse($ruleset);
            return $rule;
        }

        // Backward compat with custom functions.
        // :: any => [ func, args, ...fields ]
        if (isset($ruleset['func'])) {
            $rule = new CallbackRule();
            $rule->parse($ruleset);
            return $rule;
        }

        // Something a little more helpful.
        if (class_exists($name)) {
            throw new InvalidArgumentException("Invalid rule, not a RuleInterface: {$name}");
        }

        throw new InvalidArgumentException("Invalid rule: {$name}");
    }


    /**
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function refreshRules()
    {
        $this->setRules($this->original_rules);
    }


    /** @inheritdoc */
    public function setRules(array $rules)
    {
        $this->original_rules = $rules;

        foreach ($rules as $name => $ruleset) {

            // Custom instanced rule.
            if ($ruleset instanceof RuleInterface) {
                $this->rules[] = $ruleset;
                continue;
            }

            if (is_array($ruleset)) {

                // Backwards compat with non-keyed inline rules.
                if (is_numeric($name) and is_callable($ruleset['func'] ?? null)) {
                    $rule = new CallbackRule();
                    $rule->parse($ruleset);
                    $this->rules[] = $rule;
                    continue;
                }

                // Standard rulesets.
                if (is_string($name)) {
                    $nested = false;

                    // Backward compat - support per-field args.
                    foreach ($ruleset as $subset) {
                        if (is_array($subset)) {
                            $nested = true;
                            $this->rules[] = $this->parseRule($name, $subset);
                        }
                    }

                    // Otherwise a standard keyed ruleset.
                    if (!$nested) {
                        $this->rules[] = $this->parseRule($name, $ruleset);
                    }

                    continue;
                }

                // Nested rulesets.
                if (is_string(key($ruleset))) {
                    $ok = 0;

                    foreach ($ruleset as $subkey => $subrule) {
                        if (is_string($subkey) and is_array($subrule)) {
                            $this->rules[] = $this->parseRule($subkey, $subrule);
                            $ok++;
                        }
                    }

                    // Otherwise fallthrough to the invalid ruleset.
                    if ($ok) {
                        continue;
                    }
                }
            }

            $type = gettype($ruleset);
            throw new InvalidArgumentException("Invalid ruleset: {$name} {$type}");
        }
    }


    /** @inheritdoc */
    public function validate(): bool
    {
        foreach ($this->rules as $rule) {

            if ($rule instanceof RequiredRule) {
                $this->required($rule->fields());
                continue;
            }

            try {
                $rule->validate($this->data);
            }
            catch (ValidationException $ex) {
                if ($ex->getErrors()) {
                    foreach ($ex->errors as $field => $messages) {
                        $this->addFieldError($field, $messages);
                    }
                }
                else {
                    $fields = $rule->fields();
                    $message = $ex->getMessage();

                    $this->addMultipleFieldError($fields, $message);
                }
            }
        }

        return empty($this->errors);
    }


    /**
     * Checks various fields are required
     * If a field is required and no value is provided, no other validation will be proessed for that field.
     *
     * @param array $fields Fields to check
     */
    public function required(array $fields)
    {
        foreach ($fields as $field_name) {
            if (RequiredRule::isEmpty($this->data, $field_name)) {
                $this->errors[$field_name] = ['required' => 'This field is required'];
            }
        }
    }


    /**
     * Add an error message for a given field to the field errors list
     *
     * @param string $field_name The field to add the error message for
     * @param string|string[] $message The message text
     */
    public function addFieldError($field_name, $message)
    {
        if (is_array($message)) {
            foreach ($message as $item) {
                $this->errors[$field_name][] = $item;
            }
        }
        else {
            $this->errors[$field_name][] = $message;
        }
    }


    /**
     * Add an error message from a multiple-field validation (e.g. checking at least one is set)
     *
     * @param array $fields The fields to add the error message for
     * @param string $message The message text
     */
    public function addMultipleFieldError(array $fields, $message)
    {
        foreach ($fields as $f) {
            $this->addFieldError($f, $message);
        }
    }


    /** @inheritdoc */
    public function getErrors(): array
    {
        return $this->errors;
    }


    /** @inheritdoc */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }


    /**
     * Return all errors as a mapped array.
     *
     * @return string[] [ 'label' => 'messages' ]
     */
    public function errorsAsArray(): array
    {
        $errors = [];

        foreach ($this->errors as $field => $msgs) {
            $errors[$this->labels[$field] ?? Inflector::humanize($field)] = $msgs;
        }

        $out = [];
        foreach ($errors as $label => $msgs) {
            $out[$label] = implode('. ', $msgs);
        }

        return $out;
    }


    /**
     * Populates form field errors from ValidationException
     *
     * @param ValidationException $exception
     * @return void
     */
    public function fromValidationException(ValidationException $exception): void
    {
        foreach ($exception->getErrors() as $field => $msgs) {
            foreach ($msgs as $msg) {
                $this->addFieldError($field, $msg);
            }
        }
    }
}
