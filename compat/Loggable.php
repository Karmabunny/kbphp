<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\LogSinkInterface::class, Loggable::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\LogSinkInterface */
    interface Loggable extends \karmabunny\interfaces\LogSinkInterface {}
}
