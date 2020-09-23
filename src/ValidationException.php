<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Validator errors.
 *
 * @package karmabunny/kb
 */
class ValidationException extends \Exception {

    /**
     * Any failed properties.
     *
     * @var string[]
     */
    public $properties = [];

    /**
     * Keyed property => messages.
     *
     * @var string[]
     */
    public $errors = [];

    /**
     * Required properties.
     *
     * @var string[]
     */
    public $required = [];

}
