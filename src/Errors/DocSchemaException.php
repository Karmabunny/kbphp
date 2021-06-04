<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb\Errors;

use LibXMLError;

/**
 * An error when validating against an XSD schema.
 *
 * @package karmabunny\kb
 */
class DocSchemaException extends DocException
{
    public function __construct(LibXMLError $error)
    {
        parent::__construct($error->message, $error->code);
        $this->line = $error->line;
        $this->file = $error->file;
    }
}
