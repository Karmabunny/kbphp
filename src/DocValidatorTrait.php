<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Use '@var' comments to validate object properties.
 *
 * If using a class type:
 * 1. include the namespace\\to\\Class
 * 2. OR indicate where to find it with the namespaces() function
 *
 * E.g.
 *   /** @var Class //
 *   public $property;
 *
 *   public function namespaces() {
 *       return [ 'namespace\\to\\' ];
 *   }
 *
 * @package karmabunny/kb
 */
trait DocValidatorTrait {

    public function validate()
    {
        $valid = new DocValidator($this);
        $valid->validate();
    }
}

