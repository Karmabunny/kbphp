<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

class_alias(RulesStaticValidator::class, RulesValidator::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use either RulesStaticValidator or RulesClassValidator */
    class RulesValidator extends RulesStaticValidator {}
}