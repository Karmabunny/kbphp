<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\ArrayableInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ArrayableInterface */
    interface Arrayable extends \karmabunny\interfaces\ArrayableInterface {}
}
