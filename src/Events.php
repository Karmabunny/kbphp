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
     * [emitter][event] = [ time, time... ]
     *
     * @var float[][][]
     */
    protected static $_log = [];


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

        self::$_log[$sender][get_class($event)][] = microtime(true);

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
            if (!is_scalar($event)) {
                $event = gettype($event);
            }
            throw new InvalidArgumentException("Event '{$event}' is not an EventInterface");
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
        if ($sender === '*') {
            if ($event) {
                foreach (array_keys(self::$_events) as $sender) {
                    unset(self::$_events[$sender][$event]);
                }
            }
            else {
                self::$_events = [];
            }
        }
        else if ($event) {
            unset(self::$_events[$sender][$event]);
        }
        else {
            unset(self::$_events[$sender]);
        }
    }


    /**
     * How many times has events been triggered?
     *
     * @param array $filter [ event, sender, flatten ]
     * @return array
     */
    public static function getLogs(array $filter = []): array
    {
        $log = [];

        $filter_event = $filter['event'] ?? null;
        $filter_sender = $filter['sender'] ?? null;
        $filter_flatten = $filter['flatten'] ?? false;

        foreach (self::$_log as $sender => $events) {

            if ($filter_sender and $sender !== $filter_sender) {
                continue;
            }

            foreach ($events as $event => $logs) {
                if ($filter_event and $event !== $filter_event) {
                    continue;
                }

                if ($filter_flatten) {
                    foreach ($logs as $time) {
                        $log[] = sprintf("%.6f::%s:%s", $time, $sender, $event);
                    }
                }
                else {
                    $log[$sender][$event] = $logs;
                }
            }
        }

        if ($filter_flatten) {
            asort($log);
        }

        return $log;
    }


    public static function hasRun(string $sender, string $event): bool
    {
        if ($sender === '*') {
            foreach (array_keys(self::$_log) as $sender) {
                if (isset(self::$_log[$sender][$event])) {
                    return true;
                }
            }
        }
        else {
            return !empty(self::$_log[$sender][$event]);
        }
    }


    /**
     * Clear the event log.
     *
     * @return void
     */
    public static function clearLog()
    {
        self::$_log = [];
    }
}
