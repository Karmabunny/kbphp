<?php
namespace karmabunny\kb;

class_exists(\karmabunny\interfaces\SortableInterface::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\SortableInterface */
    interface Sortable extends \karmabunny\interfaces\SortableInterface {}
}
