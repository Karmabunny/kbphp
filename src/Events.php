<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

/**
 * A static events system.
 *
 * @package Bloom\Base
 */
class Events
{

    /**
     * [emitter][event] = [ listener, listener... ]
     *
     * @var callable[][][]
     */
    protected static $_events = [];


    /**
     * Fire an event.
     *
     * Given a class tree like:
     *
     * ```
     *        Root
     *       /   \
     *     Sub   Other
     *     /
     *   Leaf
     * ```
     *
     * Events will propagate from their respective class subtree. Such as,
     * 'Sub' will emit on both 'Sub' and 'Leaf', even though the class inherits
     * from 'Root'. Whereas events from 'Root' will emit on every class.
     *
     * Triggering events in a class that is not it's own, or even from
     * a parent class is _strongly_ discouraged.
     *
     * _Don't_ use dynamic class names, such as:
     * - `static::class`
     * - `self::class`
     * - `get_class($this)`
     *
     * @param string $sender
     * @param EventInterface $event
     * @return array[] event results.
     */
    public static function trigger(string $sender, EventInterface &$event): array
    {
        // Events are ID'd by their full namespaced class name.

        $handlers = [];

        foreach (self::$_events as $class => $events) {
            // Permit subclass emitters to trigger root handlers.
            // But not the other way - roots cannot trigger subclass handlers.
            if (!is_a($class, $sender, true)) continue;

            $some = $events[get_class($event)] ?? [];

            // Add handlers in reverse.
            // This retains the order of classes (registration order) but each
            // set of handlers are executed in reverse (LIFO).
            // This is desirable so that handlers registered later are able to
            // override or prevent behaviour of earlier handlers.
            foreach ($some as $handler) {
                array_unshift($handlers, $handler);
            }
        }

        $results = [];

        // Fire off.
        foreach ($handlers as $fn) {
            $results[] = $fn($event);
        }

        return $results;
    }


    /**
     * Listen to an event.
     *
     * This method has two signatures:
     *
     * ```
     * // Full form
     * Events::on(Emitter::class, Event::class, function(MyEvent $event) {});
     *
     * // Short form
     * Events::on(Emitter::class, function(MyEvent $event) {});
     * ```
     *
     * In the second form the event type is derived from the first parameter
     * of the handler function.
     *
     * @param string $sender
     * @param string|callable $event
     * @param callable|null $fn
     * @return void
     * @throws InvalidArgumentException
     */
    public static function on(string $sender, $event, callable $fn = null)
    {
        // If no handler is given, assume the second parameter is handler.
        // Using some cheeky reflection we can extract the event type.
        if (!$fn) {
            try {
                $fn = $event;
                $event = null;

                $reflect = new ReflectionFunction($fn);

                if (
                    ($parameters = $reflect->getParameters())
                    and ($type = $parameters[0]->getType())
                    and ($type instanceof ReflectionNamedType)
                ) {
                    $event = $type->getName();
                }
            }
            catch (ReflectionException $exception) {
                throw new InvalidArgumentException("Invalid event handler", 0, $exception);
            }
        }

        if (!is_subclass_of($event, EventInterface::class)) {
            throw new InvalidArgumentException("Not an Event subclass: {$event}");
        }


        self::$_events[$sender][$event][] = $fn;
    }


    /**
     * Remove listeners.
     *
     * @param string $sender
     * @param string|null $event
     * @return void
     */
    public static function off(string $sender, string $event = null)
    {
        if ($event) {
            unset(self::$_events[$sender][$event]);
        }
        else {
            unset(self::$_events[$sender]);
        }
    }
}
