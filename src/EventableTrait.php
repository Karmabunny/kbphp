<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;
use karmabunny\interfaces\EventInterface;

/**
 * Implements events helpers.
 *
 * @package Bloom\Base
 */
trait EventableTrait
{


    /**
     * Fire an event.
     *
     * When calling this method, it's best to avoid self/static/get_class and
     * explicitly declare which class is emitting the event.
     *
     * Although `self` is reasonably predictable, there's plenty of potential
     * for confusion - so please be cautious.
     *
     * ```
     * // Good
     * this->trigger(MyClass::class, $event);
     *
     * // Beware
     * $this->trigger(self::class, $event);
     * $this->trigger(static::class, $event);
     * $this->trigger(get_class($this), $event);
     * ```
     *
     * @see Events::trigger()
     * @param EventInterface $event
     * @return array handler results
     */
    protected function trigger(string $class, EventInterface &$event): array
    {
        if ($event instanceof Event) {
            $event->sender = $this;
        }

        return Events::trigger($class, $event);
    }


    /**
     * Listen to an event.
     *
     * This will receive events from the instance class, and events from any
     * parent class.
     *
     * ```
     * $this->on(function(MyEvent $event) {});
     * ```
     *
     * @see Events::on()
     * @param class-string<EventInterface>|callable $event
     * @param callable|bool|null $fn
     * @param bool $append
     * @return void
     * @throws InvalidArgumentException
     */
    public function on($event, $fn = null, bool $append = true)
    {
        // Unlike trigger, using dynamic class names here is OK. A user is not
        // surprised (hopefully) that they only receive events appropriate for
        // the leaf node that is 'this'.
        Events::on(static::class, $event, $fn, $append);
    }
}
