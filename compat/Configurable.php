<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\ConfigurableInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ConfigurableInterface */
    interface Configurable extends \karmabunny\interfaces\ConfigurableInterface {}
}
