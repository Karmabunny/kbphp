<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use LibXMLError;

/**
 * An error when processing XML.
 *
 * @package karmabunny\kb
 */
class XMLException extends Exception
{
    /** @var LibXMLError[] */
    public $errors = [];
}
