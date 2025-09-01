<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\ConfigurableInterface::class, Configurable::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ConfigurableInterface */
    interface Configurable extends \karmabunny\interfaces\ConfigurableInterface {}
}
