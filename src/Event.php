<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

use karmabunny\interfaces\EventInterface;

/**
 * An abstract base event.
 *
 * @package karmabunny\kb
 */
abstract class Event extends DataObject implements EventInterface
{

    /** @var object|null */
    public ?object $sender = null;

    /** @var bool */
    public bool $handled = false;

}
