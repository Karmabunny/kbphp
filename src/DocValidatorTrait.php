<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;

/**
 * Use '@var' comments to validate object properties.
 *
 * @see DocValidator
 *
 * @package karmabunny/kb
 */
trait DocValidatorTrait {


    /**
     *
     * @return void
     * @throws ValidationException
     * @throws Exception
     */
    public function validate()
    {
        $valid = new DocValidator($this);
        if (!$valid->validate()) {
            throw new ValidationException($valid->getErrors());
        }
    }


    /**
     *
     * @return bool
     * @throws Exception
     */
    public function valid()
    {
        $valid = new DocValidator($this);
        return $valid->validate();
    }
}

