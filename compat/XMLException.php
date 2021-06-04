<?php

namespace karmabunny\kb;

use karmabunny\kb\Errors\DocException;

class_alias(DocException::class, XMLException::class);

if (false) {
    class XMLException extends DocException {}
}
