<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\ValidatorInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ValidatorInterface */
    interface Validator extends \karmabunny\interfaces\ValidatorInterface {}
}
