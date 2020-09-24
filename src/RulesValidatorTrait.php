<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;

/**
 * Use validator functions to validate collection properties.
 *
 * @see RulesValidator
 * @see Validity
 *
 * @package karmabunny/kb
 */
trait RulesValidatorTrait {

    /**
     * Specify validators to run across the collection properties.
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
     *     'validity' => Validity::class,
     *     'required' => ['field1', 'field2'],
     *     '//namespace//function' => ['field1', 'field2'],
     *     'validityFunction' => ['field1', 'field2'],
     *     ['field1', '//namespace//function', 'arg1', 'arg2', ...],
     *     ['field2', [Namespace::class, 'function'], 'arg1', 'arg2', ...],
     *     ['field3', function($value) {
     *         throw ValidityError('oh noooo');
     *     }],
     * ]
     *
     * @return array
     */
    public abstract function rules(): array;


    /**
     *
     * @return void
     * @throws Exception
     * @throws ValidationException
     */
    public function validate()
    {
        $valid = new RulesValidator($this);

        $fields = $this->rules();

        if (isset($fields['validity'])) {
            $valid->setValidity($fields['validity']);
            unset($fields['validity']);
        }

        foreach ($fields as $key => $args) {
            // field => [func + args].
            if (is_int($key)) {
                $field = array_shift($args);
                $func = array_shift($args);

                if ($func === 'required') {
                    $valid->required([$field]);
                }
                else {
                    $valid->check($field, $func, ...$args);
                }
            }
            // Special condition for required fields.
            else if ($key === 'required') {
                $valid->required($args);
            }
            // func => [fields]
            else if (is_array($args)) {
                foreach ($args as $field) {
                    $valid->check($field, $key);
                }
            }
            // Ah what!
            else {
                throw new \Exception('Invalid validator');
            }
        }

        if ($valid->hasErrors()) {
            throw new ValidationException($valid->getFieldErrors());
        }
    }
}
