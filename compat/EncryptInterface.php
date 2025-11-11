<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\EncryptInterface::class, EncryptInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\EncryptInterface */
    interface EncryptInterface extends \karmabunny\interfaces\EncryptInterface {}
}
