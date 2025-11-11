<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\ValidatorInterface::class, Validator::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ValidatorInterface */
    interface Validator extends \karmabunny\interfaces\ValidatorInterface {}
}
