<?php

namespace karmabunny\kb;

use karmabunny\kb\Errors\DocParseException;

class_alias(DocParseException::class, XMLParseException::class);

if (false) {
    class XMLParseException extends DocParseException {}
}
