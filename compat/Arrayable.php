<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\ArrayableInterface::class, Arrayable::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ArrayableInterface */
    interface Arrayable extends \karmabunny\interfaces\ArrayableInterface {}
}
