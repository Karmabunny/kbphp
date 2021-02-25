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
     * There are two syntaxes:
     *
     * 1. bulk validation
     *    - doesn't support validator arguments
     *    - only supports string callables (because it's a key)
     *
     * 2. per field validation
     *    - supports validator arguments
     *    - supports array and inline callables
     *
     * Special rules exist for 'validity' and 'required':
     *
     * - 'validity' will switch out the Validity helper class for a custom one.
     *   This provides namespace free validations.
     *
     * - 'required' is a RulesValidator method and any 'required' methods in
     *   the given validity helper will be ignored.
     *
     * E.g.
     * [
     *     // Settings and special options.
     *     'validity' => Validity::class,
     *     'required' => ['field1', 'field2'],
     *     // TODO trim
     *
     *     // Bulk validation.
     *     '\\namespace\\function' => ['field1', 'field2'],
     *     'validityFunction' => ['field1', 'field2'],
     *
     *     // Per field validation.
     *     ['field1', '\\namespace\\function', 'arg1', 'arg2', ...],
     *     ['field2', [Namespace::class, 'function'], 'arg1', 'arg2', ...],
     *     ['field3', function($value) {
     *         throw ValidityError('oh noooo');
     *     }],
     * ]
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
