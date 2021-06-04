<?php

namespace karmabunny\kb;

use karmabunny\kb\Errors\DocSchemaException;

class_alias(DocSchemaException::class, XMLSchemaException::class);

if (false) {
    class XMLSchemaException extends DocSchemaException {}
}
