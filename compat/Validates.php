<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\ValidatesInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ValidatesInterface */
    interface Validates extends \karmabunny\interfaces\ValidatesInterface {}
}
