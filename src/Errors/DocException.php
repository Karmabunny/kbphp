<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\Errors;

use Exception;
use LibXMLError;

/**
 * An error when processing XML.
 *
 * @package karmabunny\kb
 */
class DocException extends Exception
{
    /** @var LibXMLError[] */
    public $errors = [];
}
