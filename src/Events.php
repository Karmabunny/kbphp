<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;
use karmabunny\interfaces\EventInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * A static events system.
 *
 * @package karmabunny\kb
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
     * @var null|(float[][][])
     */
    protected static $_log = null;


    /**
     * [ emitter, event ] = latest time
     *
     * @var float[][]
     */
    protected static $_run = [];


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
     * Avoid dynamic class names, such as:
     * - `static::class`
     * - `get_class($this)`
     *
     * @param class-string|object $sender
     * @param EventInterface $event
     * @param bool $once Don't trigger if the event has already run at least once.
     * @return array[] event results.
     */
    public static function trigger($sender, EventInterface $event, bool $once = false): array
    {
        // Events are ID'd by their full namespaced class name.
        if (is_object($sender)) {
            if ($event instanceof Event) {
                $event->sender = $sender;
            }
            $sender = get_class($sender);
        }

        // @phpstan-ignore-next-line: runtime check.
        if (!is_string($sender) or !class_exists($sender)) {
            throw new InvalidArgumentException("Sender '{$sender}' is not a class");
        }

        if ($once) {
            if (isset(self::$_run[$sender][get_class($event)])) {
                return [];
            }
        }

        $handlers = [];

        foreach (self::$_events as $class => $events) {
            // Permit subclass emitters to trigger root handlers.
            // But not the other way - roots cannot trigger subclass handlers.
            if (!is_a($class, $sender, true)) continue;

            $some = $events[get_class($event)] ?? [];
            array_push($handlers, ...$some);
        }

        $time = microtime(true);

        $results = [];

        // Fire off.
        foreach ($handlers as $fn) {
            $results[] = $fn($event);

            if ($event instanceof Event and $event->handled) {
                break;
            }
        }

        if (self::$_log !== null) {
            self::$_log[$sender][get_class($event)][] = $time;
        }

        self::$_run[$sender][get_class($event)] = $time;

        return $results;
    }


    /**
     * Listen to an event.
     *
     * This method has two key signatures:
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
     * All invocations accept the `$sender` as an object or string. Given a
     * object instance the sender is attached to the event. This then automatically
     * filters events on that sender to the appropriate instance. Filtering is
     * not applied to `EventInterface` objects not extending `Event`.
     *
     * @param class-string|object $sender
     * @param class-string<EventInterface>|callable $event
     * @param callable|bool|null $fn
     * @param bool $append
     * @return void
     * @throws InvalidArgumentException
     */
    public static function on($sender, $event, $fn = null, bool $append = true)
    {
        // If no handler is given, assume the second parameter is handler.
        // Using some cheeky reflection we can extract the event type.
        if ($fn === null or is_bool($fn)) {
            $append = $fn ?? $append;

            try {
                $fn = $event;
                $event = null;

                if (!is_callable($fn)) {
                    throw new InvalidArgumentException("Invalid event handler");
                }

                // Convert array callables.
                if (is_array($fn)) {
                    list($class, $method) = $fn;
                    $reflect = new ReflectionMethod($class, $method);
                }
                else {
                    $reflect = new ReflectionFunction($fn);
                }

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

        if (is_object($sender)) {
            if (is_a($event, Event::class, true)) {
                $fn = function(Event $event) use ($sender, $fn) {
                    if ($event->sender === $sender) {
                        return $fn($event);
                    }
                };
            }

            $sender = get_class($sender);
        }

        if (!$append and isset(self::$_events[$sender][$event])) {
            array_unshift(self::$_events[$sender][$event], $fn);
        }
        else {
            self::$_events[$sender][$event][] = $fn;
        }
    }


    /**
     * Remove listeners.
     *
     * If `$event` is not given (null), all listeners are removed from the sender.
     *
     * Specify a `null` sender to remove all listeners from an event.
     *
     * Or both `null, null` to remove _all_ listeners from all senders.
     *
     * @param class-string|object|null $sender
     * @param class-string<EventInterface>|null $event
     * @return void
     */
    public static function off($sender, ?string $event = null)
    {
        if (is_object($sender)) {
            $sender = get_class($sender);
        }

        if ($sender === null) {
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
        if (self::$_log === null) {
            return [];
        }

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


    /**
     * Has this event been triggered?
     *
     * @param string $sender
     * @param class-string<EventInterface> $event
     * @return bool
     */
    public static function hasRun(string $sender, string $event): bool
    {
        if ($sender === '*') {
            foreach (array_keys(self::$_run) as $sender) {
                if (isset(self::$_run[$sender][$event])) {
                    return true;
                }
            }
            return false;
        }
        else {
            if (!empty(self::$_run[$sender][$event])) {
                return true;
            }

            while ($sender = get_parent_class($sender)) {
                if (!empty(self::$_run[$sender][$event])) {
                    return true;
                }
            }

            return false;
        }
    }


    /**
     * Disable or enable event logging.
     *
     * Default disabled.
     *
     * @param bool $logging
     * @return void
     */
    public static function setLogging(bool $logging)
    {
        self::$_log = $logging ? [] : null;
    }


    /**
     * Clear the event log.
     *
     * @param bool $clearRunLog
     * @return void
     */
    public static function clearLog(bool $clearRunLog = false)
    {
        if (self::$_log !== null) {
            self::$_log = [];
        }

        if ($clearRunLog) {
            self::$_run = [];
        }
    }
}
