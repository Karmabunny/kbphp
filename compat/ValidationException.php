<?php

namespace karmabunny\kb;

class_alias(\karmabunny\kb\Errors\ValidationException::class, ValidationException::class);

if (false) {
    class ValidationException extends \karmabunny\kb\Errors\ValidationException {}
}
