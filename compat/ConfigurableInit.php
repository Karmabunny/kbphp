<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\ConfigurableInitInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ConfigurableInterface */
    interface ConfigurableInit extends \karmabunny\interfaces\ConfigurableInitInterface {}
}
