<?php
namespace karmabunny\kb;

class_alias(\karmabunny\interfaces\SortableInterface::class, Sortable::class);

/** @phpstan-ignore-next-line: IDE hints */
if (false) {
    /** @deprecated Use \karmabunny\interfaces\SortableInterface */
    interface Sortable extends \karmabunny\interfaces\SortableInterface {}
}
