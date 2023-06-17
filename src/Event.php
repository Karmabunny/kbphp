<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

/**
 * An abstract base event.
 *
 * @package Bloom\Base
 */
abstract class Event extends DataObject implements EventInterface
{

    /** @var object|null */
    public $sender;

    /** @var bool */
    public $handled = false;

}
