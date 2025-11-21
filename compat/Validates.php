<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\ValidatesInterface::class, Validates::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ValidatesInterface */
    interface Validates extends \karmabunny\interfaces\ValidatesInterface {}
}
