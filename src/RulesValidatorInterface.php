<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;


/**
 *
 * @package karmabunny\kb
 */
interface RulesValidatorInterface extends Validator
{

    /**
     *
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules);
}
