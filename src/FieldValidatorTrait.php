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
 *
 *
 * @package karmabunny/kb
 */
trait FieldValidatorTrait {

    /**
     * This is the validity namespace used by validate().
     *
     * @var string
     */
    protected static $validity = Validity::class;


    /**
     * Specify validators to run across the collection properties.
     *
     * There are two syntaxes:
     * 1. bulk validation
     *    - doesn't support validator arguments
     *    - only supports string callables (because it's a key)
     * 2. per field validation
     *    - supports validator arguments
     *    - supports array and inline callables
     *
     * E.g:
     * [
     *    '//namespace//function' => ['field1', 'field2'],
     *    'validityFunction' => ['field1', 'field2'],
     *    ['field1', '//namespace//function', 'arg1', 'arg2', ...],
     *    ['field2', [Namespace::class, 'function'], 'arg1', 'arg2', ...],
     *    ['field3', function($value) {
     *         throw ValidityError('oh noooo');
     *    }],
     * ]
     *
     * @return array
     */
    public abstract function fields(): array;


    /**
     *
     * @return void
     * @throws Exception
     * @throws ValidationException
     */
    public function validate()
    {
        $valid = new Validator($this);
        $valid->setValidity(self::$validity);

        $fields = $this->fields();

        foreach ($fields as $key => $field) {
            // field => [func + args].
            if (is_int($key)) {
                call_user_func_array([$valid, 'check'], $field);
            }
            // Special condition for required fields.
            else if ($key === 'required') {
                $valid->required($field);
            }
            // func => [fields]
            else if (is_array($field)) {
                foreach ($field as $item) {
                    $valid->check($item, $key);
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
