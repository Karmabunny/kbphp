<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\InflectorInterface::class, InflectorInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\InflectorInterface */
    interface InflectorInterface extends \karmabunny\interfaces\InflectorInterface {}
}
