<?php

namespace karmabunny\kb;

/**
 * This extends the Validates interface with a `valid()` method that returns a
 * boolean. The errors are stored in the model.
 *
 * @package karmabunny\kb
 */
trait ValidErrorsTrait
{

    /**
     * This is a collection of error sets.
     *
     * @var array [ scenario => [ item => [errors] ] ]
     */
    private $_errors = [];


    /**
     * Perform validation.
     *
     * @throws ValidationException
     */
    public abstract function validate(string $scenario = null);


    /**
     * Perform validation, errors are stored on the model.
     *
     * @param string|null $scenario
     * @return bool true if valid, false otherwise.
     */
    public function valid(string $scenario = null): bool
    {
        try {
            $this->validate($scenario);
            return true;
        }
        catch (ValidationException $exception) {
            $key = $scenario ?? '';

            // Ensure the latest scenario is always last.
            if (isset($this->_errors[$key])) {
                unset($this->_errors[$key]);
            }

            $this->_errors[$key] = $exception->errors;
            return false;
        }
    }


    /**
     * Get validation errors.
     *
     * This is a nested array of errors.
     *
     * Like this:
     * ```
     * [
     *   'scenario1' => [
     *      'item1' => [ 'error1', 'error2' ],
     *      'item2' => [ 'error3', 'error4' ],
     *   ],
     *   'scenario2' => [
     *      'item1' => [ 'etc' ],
     *   ],
     * ]
     * ```
     *
     * @return array [ scenario => [ item => [errors] ] ]
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }


    /**
     * Get a summary of all errors.
     *
     * @return string[]
     */
    public function getErrorSummaries(): array
    {
        $summaries = [];

        foreach ($this->_errors as $errors) {
            $summaries[] = ValidationException::getSummary($errors);
        }

        return $summaries;
    }


    /**
     * Get the latest error set.
     *
     * @return string[]
     */
    public function getLastErrors(): array
    {
        return Arrays::last($this->_errors);
    }


    /**
     * Get the latest error message.
     *
     * @return string|null
     */
    public function getLastErrorSummary()
    {
        $errors = Arrays::last($this->_errors);
        if (!$errors) return null;

        return ValidationException::getSummary($errors);
    }


    /**
     * Has validation errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->_errors);
    }


    /**
     * Remove all errors.
     *
     * @return void
     */
    public function clearError()
    {
        $this->_errors = [];
    }

}
