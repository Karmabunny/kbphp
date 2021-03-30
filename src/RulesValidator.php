<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use ArrayObject;
use Exception;
use InvalidArgumentException;


/**
 * Rules based validation processor.
 *
 * Used with the {@see Validity} class. This can be changed with setValidity().
 *
 * @example
 *    // Plain example
 *
 *    $valid = new Validator($_POST);
 *
 *    $valid->required(['name', 'email']);
 *
 *    $valid->check('name', 'Validity::length', 1, 100);
 *    $valid->check('email', 'Validity::email');
 *
 *    if ($valid->hasErrors()) {
 *        $_SESSION['register']['field_errors'] = $valid->getFieldErrors();
 *        Url::redirect('user/register');
 *    }
 * @example
 *    // Multiedit example for a course with students
 *
 *    $has_error = false;
 *
 *    $valid = new Validator($_POST);
 *    $valid->required(['name']);
 *    $valid->check('name', 'Validity::length', 1, 100);
 *
 *    if ($valid->hasErrors()) {
 *        $_SESSION['course_edit']['field_errors'] = $valid->getFieldErrors();
 *        $has_error = true;
 *    }
 *
 *    if (empty($_POST['multiedit_students'])) {
 *        $_POST['multiedit_students'] = [];
 *    }
 *
 *    $record_num = 0;
 *    foreach ($_POST['multiedit_students'] as $idx => $data) {
 *        if (MultiEdit::recordEmpty($data)) continue;
 *
 *        ++$record_num;
 *
 *        $multi_valid = new Validator($data);
 *        $multi_valid->setLabels([
 *            'name' => 'Name for student ' . $record_num,
 *            'email' => 'Email address for student ' . $record_num,
 *        ]);
 *
 *        $multi_valid->required(['name', 'email']);
 *        $multi_valid->check('name', 'Validity::length', 1, 100);
 *        $multi_valid->check('email', 'Validity::email');
 *
 *        if ($multi_valid->hasErrors()) {
 *            $_SESSION['course_edit']['field_errors']['multiedit_students'][$idx] = $multi_valid->getFieldErrors();
 *            $has_error = true;
 *        }
 *    }
 *
 *    if ($has_error) {
 *        Url::redirect('course/edit');
 *    }
 *
 * @package karmabunny\kb
 */
class RulesValidator implements Validator
{
    protected $labels;
    protected $data;
    protected $rules;
    protected $field_errors;
    protected $general_errors;
    protected $validity;

    /**
     * Recursive trim data
     *
     * Alters in-place AND returns the array
     * This allows for use such as:
     *
     *    $_SESSION['register']['field_values'] = Validator::trim($_POST);
     *
     * When used like this, the session gets set and the POST data is also trimmed,
     * so can be used directly for database inserts.
     *
     * @param array $data Data to trim. Passed by-reference.
     * @return array Trimmed data
     */
    public static function trim(array &$data)
    {
        foreach ($data as $key => $val) {
            if (is_string($val)) {
                $data[$key] = trim($val);
            } elseif (is_array($val)) {
                self::trim($val);
            }
        }

        return $data;
    }


    /**
     * @param array|object $data Data to validate
     */
    public function __construct($data, array $rules = [])
    {
        if (is_array($data) or $data instanceof ArrayAccess) {
            $this->data = $data;
        }
        else {
            // An object with no ArrayAccess, so we're wrapping it up!
            // It's easier to just use a Collection - so please do.
            $this->data = new ArrayObject($data, ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);
        }

        $this->labels = null;
        $this->rules = $rules;
        $this->field_errors = [];
        $this->general_errors = [];
        $this->validity = Validity::class;
    }


    /**
     * Field labels make error messages a little friendlier
     *
     * @param array $labels Field labels
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }


    /**
     * Update the data to validate
     *
     * @param array|object $data Data to validate
     */
    public function setData($data)
    {
        $this->data = $data;
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
     * Set the label for a single field
     *
     * @param string $field The field to set
     * @param string $label The label to set on the field
     */
    public function setFieldLabel($field, $label)
    {
        $this->labels[$field] = $label;
    }


    /**
     * Set the validity helper.
     *
     * @param string $class
     */
    public function setValidity(string $class)
    {
        if (!class_exists($class)) {
            throw new Exception("Invalid validity class: {$class}");
        }
        $this->validity = $class;
    }


    /**
     * For a given function, search if it exists in the {@see Validity} class.
     *
     * @param callable|string $func The function to expand.
     * @return callable|false False if not callable.
     */
    protected function expandNs($func)
    {
        // Check for methods on a validity class first.
        $expanded = [$this->validity, $func];
        if (is_callable($expanded)) {
            return $expanded;
        }

        // Then fall back to whatever.
        if (is_callable($func)) {
            return $func;
        }

        return false;
    }


    /**
     * Validate properties in an object as defined the rules config.
     *
     * @see RulesValidatorTrait
     * @return bool True if valid. False if there were errors.
     * @throws Exception
     */
    public function validate(): bool
    {
        $rules = $this->rules;

        // Optionally swap out for a different validity class.
        if (isset($rules['validity'])) {
            $this->setValidity($rules['validity']);
            unset($rules['validity']);
        }

        foreach ($rules as $rule => $ruleset) {
            unset($func);

            // Required validations are a bit special.
            if ($rule === 'required') {
                $this->required($ruleset);
                continue;
            }

            // Look for custom validators first.
            $func = $ruleset['func'] ?? null;

            if ($func and !is_callable($func)) {
                throw new Exception("Invalid rule: {$rule} - {$func}");
            }

            // Then check the builtin rules.
            else if (!$func) {
                $func = $this->expandNs($rule);
            }

            if (!$func) {
                throw new Exception("Unknown rule: {$rule}");
            }

            // Big old-loopy-do.
            foreach ($ruleset as $key => $fieldset) {
                if (!is_numeric($key)) continue;
                if (empty($fieldset)) continue;

                $field = $fieldset;
                $args = $ruleset['args'] ?? [];

                // Field with args.
                if (is_array($fieldset)) {
                    $field = $fieldset[0];

                    if (is_array(@$fieldset['args'])) {
                        $args = $fieldset['args'];
                    }
                }

                $value = $this->data[$field] ?? null;

                // Empty field are valid - these are checked by required.
                if ($value === null) continue;
                if (self::isEmpty($value)) continue;

                try {
                    // We're also re-writing the value if not-null.
                    // This lets built-ins like 'trim' do their thing.
                    if ($value = $func($value, ...$args)) {
                        $this->data[$field] = $value;
                    }
                }
                catch (ValidationException $ex) {
                    $this->addFieldError($field, $ex->getMessage());
                }
            }
        }

        return empty($this->field_errors);
    }


    /**
     * Check the value of a field against a validation method, storing any error messages received
     * Additional arguments are passed to the underlying method
     *
     * If a field has already been checked with {@see Validator::required} and the field was empty,
     * this function will not report errors (but will still return an appropriate value)
     *
     * If a empty value is provided, it is not validated - returns true
     *
     * @param string $field_name The field to check
     * @param callable $func The function or method to call.
     * @param array $args
     * @return bool True if validation was successful, false if it failed
     */
    public function check($field_name, $func, ...$args)
    {
        $value = $this->data[$field_name] ?? null;

        if ($value === null) return true;
        if (self::isEmpty($value)) return true;

        $func = $this->expandNs($func);

        try {
            $func($value, ...$args);
            return true;

        } catch (ValidationException $ex) {
            $this->addFieldError($field_name, $ex->getMessage());
            return false;
        }
    }


    /**
     * Run a validation check against each value in an array.
     * Behaviour is very similar to the {@see Validator::check} method.
     *
     * Only supports single-depth arrays
     *
     * Errors are put into the field_errors array under a subkey matching the array key
     *
     * Return value is an array of key => boolean with the validation result for each key
     *
     * @example
     *    $data = ['vals' => [1, 2, 'A', 'B', 5]];
     *    $validator = new Validator($data);
     *    $result = $validator->arrayCheck('vals', 'Validity::positiveInt');
     *    // $result now contains [true, true, false, false, true]
     *    $errs = $validator->getFieldErrors();
     *    // $errs now contains [ 'vals' => [2 => [...], 3 => [...]] ]
     *
     * @param string $field_name The field to check
     * @param callable $func The function or method to call.
     * @param array $args
     * @return array Key => Boolean True if validation was successful, false if it failed
     */
    public function arrayCheck($field_name, $func, ...$args)
    {
        $values = $this->data[$field_name] ?? null;

        if ($values === null) return [];
        if (self::isEmpty($values)) return [];

        if (!is_array($values)) {
            throw new InvalidArgumentException("Field <{$field_name}> is not an array");
        }

        $func = $this->expandNs($func);

        $results = [];
        foreach ($values as $index => $value) {
            try {
                $func($value, ...$args);
                $results[$index] = true;

            } catch (ValidationException $ex) {
                $this->addArrayFieldError($field_name, $index, $ex->getMessage());
                $results[$index] = false;
            }
        }

        return $results;
    }


    /**
     * Check multiple fields against a validation method
     *
     * This is similar to {@see Validator::check} but it's designed for different validation
     * methods, which work on a set of fields instead of a single field (e.g. Validity::oneRequired)
     *
     * Additional arguments are passed to the underlying method
     *
     * @param array $fields The fields to check
     * @param callable $func The function or method to call.
     * @param array $args
     * @return bool True if validation was successful, false if it failed
     */
    public function multipleCheck(array $fields, $func, ...$args)
    {
        $this->expandNs($func);

        $vals = [];
        foreach ($fields as $field_name) {
            $vals[] = @$this->data[$field_name];
        }

        try {
            $func($vals, ...$args);
            return true;

        } catch (ValidationException $ex) {
            $this->addMultipleFieldError($fields, $ex->getMessage());
            return false;
        }
    }


    /**
     * Sadly, the PHP builtin empty() considers '0' to be empty, but it actually isn't
     *
     * @param mixed $val
     * @return bool True if empty, false if not.
     */
    public static function isEmpty($val)
    {
        if (is_array($val) and count($val) == 0) {
            return true;
        } else if ($val == '') {
            return true;
        }
        return false;
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
            if (!isset($this->data[$field_name])) {
                $this->field_errors[$field_name] = ['required' => 'This field is required'];
            } elseif (self::isEmpty($this->data[$field_name])) {
                $this->field_errors[$field_name] = ['required' => 'This field is required'];
            }
        }
    }


    /**
     * Add an error message for a given field to the field errors list
     *
     * @param string $field_name The field to add the error message for
     * @param string $message The message text
     */
    public function addFieldError($field_name, $message)
    {
        if (!isset($this->field_errors[$field_name])) {
            $this->field_errors[$field_name] = [$message];
        } else {
            $this->field_errors[$field_name][] = $message;
        }
    }


    /**
     * Add an error message for a given field to the field errors list
     * This variation is for array validation, e.g. an array of integers
     *
     * @param string $field_name The field to add the error message for
     * @param int $index The array index of the field to report error for
     * @param string $message The message text
     */
    public function addArrayFieldError($field_name, $index, $message)
    {
        if (!isset($this->field_errors[$field_name])) {
            $this->field_errors[$field_name] = [];
        }
        if (!isset($this->field_errors[$field_name][$index])) {
            $this->field_errors[$field_name][$index] = [$message];
        } else {
            $this->field_errors[$field_name][$index][] = $message;
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


    /**
     * Get an array of all field errors, indexed by field name
     * Fields may have multiple errors defined
     *
     * @return array
     */
    public function getFieldErrors()
    {
        return $this->field_errors;
    }


    /**
     * Get validation errors (field errors).
     *
     * This is to appease the interface gods.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->field_errors;
    }


    /**
     * Add a general error message, e.g. for errors affecting many fields
     *
     * @param string $message The message text
     */
    public function addGeneralError($message)
    {
        $this->general_errors[] = $message;
    }


    /**
     * Get an array of all general errors
     *
     * @return array
     */
    public function getGeneralErrors()
    {
        return $this->general_errors;
    }


    /**
     * @return bool True if there were any validation errors, false if there wasn't
     */
    public function hasErrors(): bool
    {
        if (count($this->field_errors)) return true;
        if (count($this->general_errors)) return true;
        return false;
    }

}
