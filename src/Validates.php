<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This class has logging capabilities.
 *
 * An implementation is provided in {@see ValidatorTrait}.
 *
 * @package karmabunny/kb
 */
interface Validates {

    /**
     * Perform validation.
     *
     * @throws ValidationException
     */
    public function validate();

}
