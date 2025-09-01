<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\ConfigurableInitInterface::class, ConfigurableInit::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\ConfigurableInterface */
    interface ConfigurableInit extends \karmabunny\interfaces\ConfigurableInitInterface {}
}
