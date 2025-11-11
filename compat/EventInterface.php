<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\EventInterface::class, EventInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\EventInterface */
    interface EventInterface extends \karmabunny\interfaces\EventInterface {}
}
