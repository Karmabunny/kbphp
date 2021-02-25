<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;

/**
 * Use validator functions to validate properties.
 *
 * @see RulesValidator
 * @see Validity
 *
 * @package karmabunny\kb
 */
trait RulesValidatorTrait
{
    /**
     * Specify validators to run across the object properties.
     *
     * The syntax is as follow:
     *
     * ```
     * // A built-in rule from the 'Validity' class.
     * 'rule-name' => ['field1', 'field2']
     *
     * // A custom validity class.
     * 'exotic-rule' => ['field1', 'field2'],
     * 'validity' => MyValidity::class,
     *
     * // Specifying rule arguments.
     * 'rule-name' => ['field1', 'field2', 'args' => [0, 10]]
     *
     * // A custom inline validator.
     * 'rule-name' => ['field1', 'field2', 'func' => 'trim']
     * ```
     *
     * Special rules exist for 'validity' and 'required':
     *
     * The 'validity' rule will switch out the Validity helper class for a
     * custom one. It's probably best to inherit the built-in Validity class
     * so you don't break any inherited rules.
     *
     * ```
     * 'validity' => Validity:class
     * ```
     *
     * The 'required' rule cannot accept 'args' or 'func'.
     *
     * ```
     * 'required' => ['field1', 'field2']
     * ```
     *
     * Custom inline validators will ignore the 'rule-name'. You can instead
     * use this as a sort of ID to reuse the in inherited classes.
     *
     * ```
     * // Parent class.
     * 'my-rule' => ['field1', 'func' => 'trim']
     *
     * // Child class.
     * $rules = parent::rules($scenario);
     * $rules['my-rule'][] = 'field2';
     * ```
     *
     * Refer to the {@see Validity} class for more about validator functions.
     *
     * @return array
     */
    public abstract function rules(string $scenario = null): array;


    /**
     * Validate this scenario (or default).
     *
     * This will throw if invalid or pass silently if valid.
     *
     * @param string|null $scenario null for default.
     * @return void
     * @throws Exception
     * @throws ValidationException
     */
    public function validate(string $scenario = null) {
        $errors = $this->valid($scenario);
        if ($errors !== true) {
            throw (new ValidationException)->addErrors($errors);
        }
    }


    /**
     * Validate this scenario (or default).
     *
     * @param string|null $scenario null for default.
     * @return array|true True if valid, errors array if invalid.
     * @throws Exception
     */
    public function valid(string $scenario = null)
    {
        $rules = $this->rules($scenario);
        $valid = new RulesValidator($this, $rules);

        if (!$valid->validate()) {
            return $valid->getErrors();
        }
        return true;
    }
}
