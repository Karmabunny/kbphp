<?php

namespace karmabunny\kb;

use karmabunny\kb\Errors\DocAssertException;

class_alias(DocAssertException::class, XMLAssertException::class);

if (false) {
    class XMLAssertException extends DocAssertException {}
}
