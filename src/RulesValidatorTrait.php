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
 * RulesValidator uses array access, so an ArrayAccess interface is also enforced.
 *
 * @see RulesValidator
 * @see Validity
 *
 * @package karmabunny/kb
 */
trait RulesValidatorTrait {

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

    public abstract function offsetExists($offset);

    public abstract function offsetGet($offset);

    public abstract function offsetSet($offset, $value);

    public abstract function offsetUnset($offset);


    /**
     *
     * @return void
     * @throws Exception
     * @throws ValidationException
     */
    public function validate() {
        $valid = new RulesValidator($this);
        if (!$valid->validate()) {
            throw new ValidationException($valid->getErrors());
        }
    }


    /**
     *
     * @return bool
     * @throws Exception
     */
    public function valid(): bool
    {
        $valid = new RulesValidator($this);
        return $valid->validate();
    }
}
