<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\LogSinkInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\LogSinkInterface */
    interface Loggable extends \karmabunny\interfaces\LogSinkInterface {}
}
