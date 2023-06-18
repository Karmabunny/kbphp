<?php

use karmabunny\kb\Event;
use karmabunny\kb\Events;
use karmabunny\kb\EventableTrait;
use PHPUnit\Framework\TestCase;


class EventTest extends TestCase
{

    public function setUp(): void
    {
        EventsReset::reset();
    }


    /**
     * Root listeners receive root events.
     */
    public function testBasic()
    {
        $count = 0;

        // Instance long form.
        $emitter = new RootEmitter();
        $emitter->on(TestEvent::class, function() use (&$count) {
            $count++;
            return 'one';
        });

        $actual = $emitter->testRoot();

        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);
        $this->assertEquals(['one'], $actual);

        // Instance short form.
        $emitter->on(function(TestEvent $event) use (&$count) {
            $count++;
            return 'two';
        });

        $count = 0;
        $actual = $emitter->testRoot();

        $this->assertEquals(2, $count);
        $this->assertCount(2, $actual);
        $this->assertEquals(['two', 'one'], $actual);

        // Static long form.
        Events::on(RootEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'three';
        });

        $count = 0;
        $actual = $emitter->testRoot();

        $this->assertEquals(3, $count);
        $this->assertCount(3, $actual);
        $this->assertEquals(['three', 'two', 'one'], $actual);

        // Static short form.
        Events::on(RootEmitter::class, function(TestEvent $event) use (&$count) {
            $count++;
            return 'four';
        });

        $count = 0;
        $actual = $emitter->testRoot();

        $this->assertEquals(4, $count);
        $this->assertCount(4, $actual);
        $this->assertEquals(['four', 'three', 'two', 'one'], $actual);
    }


    /**
     * Sub class listeners receive subclass events.
     */
    public function testSubclass()
    {
        $count = 0;

        Events::on(SubEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return true;
        });

        $emitter = new SubEmitter();

        // from ROOT to SUB works.
        $actual = $emitter->testRoot();
        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);

        // from SELF (root) to SUB works.
        $actual = $emitter->testSelf();
        $this->assertEquals(2, $count);
        $this->assertCount(1, $actual);

        // from THIS (sub) to SUB works.
        $actual = $emitter->testDynamic();
        $this->assertEquals(3, $count);
        $this->assertCount(1, $actual);
    }


    /**
     * Sub class listeners receive root events.
     *
     * This test is identical to the previous. The emitter type is only
     * relevant to 'dynamic' events - which are discouraged.
     */
    public function testSubclassIndirect()
    {
        $count = 0;

        Events::on(SubEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return true;
        });

        $emitter = new RootEmitter();

        // from ROOT to SUB works.
        $actual = $emitter->testRoot();
        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);

        // from SELF (root) to SUB works.
        $actual = $emitter->testSelf();
        $this->assertEquals(2, $count);
        $this->assertCount(1, $actual);

        // from THIS (root) to SUB works.
        $actual = $emitter->testDynamic();
        $this->assertEquals(3, $count);
        $this->assertCount(1, $actual);
    }


    /**
     * Root listeners _do not_ receive subclass events.
     */
    public function testSubclassInverse()
    {
        $count = 0;

        Events::on(RootEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return true;
        });

        $emitter = new SubEmitter();

        // from SUB to ROOT fails.
        $actual = $emitter->testSub();
        $this->assertEquals(0, $count);
        $this->assertCount(0, $actual);

        // from THIS (sub) to ROOT fails.
        $actual = $emitter->testDynamic();
        $this->assertEquals(0, $count);
        $this->assertCount(0, $actual);

        // from ROOT to ROOT works.
        $actual = $emitter->testRoot();
        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);

        // from SELF (root) to ROOT fails;
        $actual = $emitter->testSelf();
        $this->assertEquals(2, $count);
        $this->assertCount(1, $actual);
    }


    /**
     * Sub listeners _do not_ receive events from siblings.
     */
    public function testSiblings()
    {
        $count = 0;

        Events::on(OtherEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return true;
        });

        $emitter = new SubEmitter();

        // from SUB to OTHER fails.
        $actual = $emitter->testSub();
        $this->assertEquals(0, $count);
        $this->assertCount(0, $actual);

        // from THIS (sub) to OTHER fails.
        $actual = $emitter->testDynamic();
        $this->assertEquals(0, $count);
        $this->assertCount(0, $actual);

        // from ROOT to OTHER works.
        $actual = $emitter->testRoot();
        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);

        // from SELF (root) to OTHER works.
        $actual = $emitter->testSelf();
        $this->assertEquals(2, $count);
        $this->assertCount(1, $actual);
    }


    /**
     * Leaf nodes receive all events, siblings and roots do not.
     */
    public function testNested()
    {
        $count = 0;

        Events::on(RootEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'root';
        });

        Events::on(SubEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'sub';
        });

        Events::on(LeafEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'leaf1';
        });

        Events::on(LeafEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'leaf2';
        });

        Events::on(OtherEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'other';
        });

        $emitter = new LeafEmitter();

        // All classes receive an event from 'root'.
        // from ROOT to ALL
        $actual = $emitter->testRoot();
        $this->assertEquals(5, $count);
        $this->assertCount(5, $actual);
        $this->assertEquals(['other', 'leaf2', 'leaf1', 'sub', 'root'], $actual);

        // Partial tree of events.
        // from SUB to SUB + LEAF
        $count = 0;
        $actual = $emitter->testSub();
        $this->assertEquals(3, $count);
        $this->assertCount(3, $actual);
        $this->assertEquals(['leaf2', 'leaf1', 'sub'], $actual);

        // from LEAF to LEAF
        $count = 0;
        $actual = $emitter->testLeaf();
        $this->assertEquals(2, $count);
        $this->assertCount(2, $actual);
        $this->assertEquals(['leaf2', 'leaf1'], $actual);

        // from SELF (root) to LEAF
        $count = 0;
        $actual = $emitter->testSelf();
        $this->assertEquals(5, $count);
        $this->assertCount(5, $actual);
        $this->assertEquals(['other', 'leaf2', 'leaf1', 'sub', 'root'], $actual);

        // from THIS (leaf) to LEAF
        $count = 0;
        $actual = $emitter->testDynamic();
        $this->assertEquals(2, $count);
        $this->assertCount(2, $actual);
        $this->assertEquals(['leaf2', 'leaf1'], $actual);

        $emitter = new SubEmitter();

        // Dynamic events from SubEmitter will trigger for both sub and leaf.
        // from THIS (sub) to LEAF
        $count = 0;
        $actual = $emitter->testDynamic();
        $this->assertEquals(3, $count);
        $this->assertCount(3, $actual);
        $this->assertEquals(['leaf2', 'leaf1', 'sub'], $actual);
    }


    public function testLogs()
    {
        $this->testNested();

        // Get all logs, nested structure.
        $logs = Events::getLogs();
        $this->assertCount(3, $logs);

        $this->assertArrayHasKey(RootEmitter::class, $logs);
        $this->assertArrayHasKey(SubEmitter::class, $logs);
        $this->assertArrayHasKey(LeafEmitter::class, $logs);

        $this->assertCount(1, $logs[RootEmitter::class]);
        $this->assertCount(1, $logs[SubEmitter::class]);
        $this->assertCount(1, $logs[LeafEmitter::class]);

        $this->assertArrayHasKey(TestEvent::class, $logs[RootEmitter::class]);
        $this->assertArrayHasKey(TestEvent::class, $logs[SubEmitter::class]);
        $this->assertArrayHasKey(TestEvent::class, $logs[LeafEmitter::class]);

        $this->assertCount(2, $logs[RootEmitter::class][TestEvent::class]);
        $this->assertCount(2, $logs[SubEmitter::class][TestEvent::class]);
        $this->assertCount(2, $logs[LeafEmitter::class][TestEvent::class]);

        Events::clearLog();
        $logs = Events::getLogs();
        $this->assertCount(0, $logs);
    }


    public function testLogsFilter()
    {
        $this->testNested();

        $logs = Events::getLogs(['sender' => LeafEmitter::class]);
        $this->assertCount(1, $logs);
        $this->assertCount(1, $logs[LeafEmitter::class]);

        $event = new TestOtherEvent();
        Events::trigger(LeafEmitter::class, $event);

        $logs = Events::getLogs(['sender' => LeafEmitter::class]);
        $this->assertCount(1, $logs);
        $this->assertCount(2, $logs[LeafEmitter::class]);

        $logs = Events::getLogs(['event' => TestEvent::class]);
        $this->assertCount(3, $logs);
        $this->assertCount(1, $logs[LeafEmitter::class]);

        $logs = Events::getLogs(['event' => TestOtherEvent::class]);
        $this->assertCount(1, $logs);
        $this->assertCount(1, $logs[LeafEmitter::class]);

        $logs = Events::getLogs(['sender' => LeafEmitter::class, 'event' => TestEvent::class]);
        $this->assertCount(1, $logs);
        $this->assertCount(1, $logs[LeafEmitter::class]);
    }


    public function testLogsFlat()
    {
        $this->testNested();

        $logs = Events::getLogs(['flatten' => true]);
        $this->assertCount(6, $logs);

        foreach ($logs as $item) {
            $this->assertStringStartsWith((string) floor(time() / 10), $item);
            $this->assertStringEndsWith('TestEvent', $item);
        }
    }
}


class TestEvent extends Event {}
class TestOtherEvent extends Event {}


class EventsReset extends Events
{
    public static function reset()
    {
        Events::$_events = [];
        Events::clearLog();
    }
}


/**
 * The class hierarchy looks like this:
 *
 *        Root
 *       /   \
 *     Sub   Other
 *     /
 *   Leaf
 *
 * - Children can receive all parent events.
 * - Parents cannot receive child events.
 */
class RootEmitter
{
    use EventableTrait;

    public function testRoot(): array
    {
        $event = new TestEvent();
        $results = $this->trigger(RootEmitter::class, $event);
        return $results;
    }

    public function testSelf(): array
    {
        $event = new TestEvent();
        $results = $this->trigger(self::class, $event);
        return $results;
    }

    public function testDynamic(): array
    {
        $event = new TestEvent();
        $results = $this->trigger(get_class($this), $event);
        return $results;
    }
}


class SubEmitter extends RootEmitter
{
    public function testSub(): array
    {
        $event = new TestEvent();
        $results = $this->trigger(SubEmitter::class, $event);
        return $results;
    }
}


class LeafEmitter extends SubEmitter
{
    public function testLeaf(): array
    {
        $event = new TestEvent();
        $results = $this->trigger(LeafEmitter::class, $event);
        return $results;
    }
}


class OtherEmitter extends RootEmitter
{
    public function testSub(): array
    {
        $event = new TestEvent();
        $results = $this->trigger(OtherEmitter::class, $event);
        return $results;
    }
}
