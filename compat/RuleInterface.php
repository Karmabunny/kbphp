<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\RuleInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\RuleInterface */
    interface RuleInterface extends \karmabunny\interfaces\RuleInterface {}
}
