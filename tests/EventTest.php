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


    public function dataWithLogs(): array
    {
        return [
            'with logs' => [true],
            'without logs' => [false],
        ];
    }


    /**
     * Root listeners receive root events.
     */
    public function testBasic()
    {
        $count = 0;

        // Instance long form.
        $emitter = new RootEmitter();

        $this->assertFalse(Events::hasListeners($emitter, TestEvent::class));
        $this->assertFalse(Events::hasListeners(get_class($emitter), TestEvent::class));
        $this->assertFalse($emitter->hasEventListeners(TestEvent::class));

        $emitter->on(TestEvent::class, function(TestEvent $event) use (&$count, $emitter) {
            $this->assertEquals($event->sender, $emitter);
            $count++;
            return 'one';
        });

        $this->assertTrue(Events::hasListeners($emitter, TestEvent::class));
        $this->assertTrue(Events::hasListeners(get_class($emitter), TestEvent::class));
        $this->assertTrue($emitter->hasEventListeners(TestEvent::class));

        $actual = $emitter->testRoot();

        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);
        $this->assertEquals(['one'], $actual);

        // Instance short form.
        $emitter->on(function(TestEvent $event) use (&$count, $emitter) {
            $this->assertEquals($event->sender, $emitter);
            $count++;
            return 'two';
        });

        $this->assertTrue($emitter->hasEventListeners(TestEvent::class));

        $count = 0;
        $actual = $emitter->testRoot();

        $this->assertEquals(2, $count);
        $this->assertCount(2, $actual);
        $this->assertEquals(['one', 'two'], $actual);

        // Static long form.
        Events::on(RootEmitter::class, TestEvent::class, function() use (&$count) {
            $count++;
            return 'three';
        });

        $count = 0;
        $actual = $emitter->testRoot();

        $this->assertEquals(3, $count);
        $this->assertCount(3, $actual);
        $this->assertEquals(['one', 'two', 'three'], $actual);

        // Static short form.
        Events::on(RootEmitter::class, function(TestEvent $event) use (&$count) {
            $count++;
            return 'four';
        });

        $count = 0;
        $actual = $emitter->testRoot();

        $this->assertEquals(4, $count);
        $this->assertCount(4, $actual);
        $this->assertEquals(['one', 'two', 'three', 'four'], $actual);
    }


    public function testPrepend()
    {

        Events::on(RootEmitter::class, function(TestEvent $event) {
            $this->assertNull($event->sender);
            return 'one';
        });

        Events::on(RootEmitter::class, function(TestEvent $event) {
            $this->assertNull($event->sender);
            return 'two';
        }, append: false);

        // Test parent class listener.
        Events::on(RootEmitter::class, TestEvent::class, function(Event $event) {
            $this->assertNull($event->sender);
            return 'three';
        }, true);

        Events::on(RootEmitter::class, TestEvent::class, function() {
            return 'four';
        }, false);

        $event = new TestEvent();
        $actual = Events::trigger(RootEmitter::class, $event);

        $this->assertCount(4, $actual);
        $this->assertEquals(['four', 'two', 'one', 'three'], $actual);
    }


    public function testHandled()
    {
        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'one';
        });

        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'two';
        });

        Events::on(RootEmitter::class, function(TestEvent $event) {
            $event->handled = true;
            return 'three';
        }, append: false);

        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'four';
        }, append: false);

        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'five';
        }, append: true);

        $event = new TestEvent();
        $actual = Events::trigger(RootEmitter::class, $event);

        $this->assertCount(2, $actual);
        $this->assertEquals(['four', 'three'], $actual);
    }


    /**
     * Test removing listeners.
     *
     * Covers every off() invocation: an object or class sender, one event or
     * every event, a null sender, and the instance helpers. Root emitters are
     * removed on their own class and still see subclass listeners afterwards.
     */
    public function testOff()
    {
        $emitter = new RootEmitter();

        $this->assertFalse(Events::hasListeners($emitter, TestEvent::class));
        $this->assertFalse(Events::hasListeners($emitter, TestOtherEvent::class));

        // Object sender, one event.
        Events::on($emitter, function(TestEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'one';
        });

        Events::on($emitter, function(TestOtherEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'two';
        });

        $this->assertTrue(Events::hasListeners($emitter, TestEvent::class));
        $this->assertTrue(Events::hasListeners($emitter, TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals(['one'], $actual);

        $actual = Events::trigger($emitter, new TestOtherEvent());
        $this->assertEquals(['two'], $actual);

        Events::off($emitter, TestEvent::class);

        $this->assertFalse(Events::hasListeners($emitter, TestEvent::class));
        $this->assertTrue(Events::hasListeners($emitter, TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals([], $actual);

        $actual = Events::trigger($emitter, new TestOtherEvent());
        $this->assertEquals(['two'], $actual);

        // Object sender, every event on that sender.
        Events::on($emitter, function(TestEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'three';
        });

        $this->assertTrue(Events::hasListeners($emitter, TestEvent::class));

        Events::off($emitter);

        $this->assertFalse(Events::hasListeners($emitter, TestEvent::class));
        $this->assertFalse(Events::hasListeners($emitter, TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals([], $actual);

        $actual = Events::trigger($emitter, new TestOtherEvent());
        $this->assertEquals([], $actual);

        // Instance helper, one event, then every event.
        $emitter->on(function(TestEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'four';
        });

        $emitter->on(function(TestOtherEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'five';
        });

        $this->assertTrue($emitter->hasEventListeners(TestEvent::class));
        $this->assertTrue($emitter->hasEventListeners(TestOtherEvent::class));

        $emitter->off(TestEvent::class);

        $this->assertFalse($emitter->hasEventListeners(TestEvent::class));
        $this->assertTrue($emitter->hasEventListeners(TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals([], $actual);

        $actual = Events::trigger($emitter, new TestOtherEvent());
        $this->assertEquals(['five'], $actual);

        $emitter->on(function(TestEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'six';
        });

        $emitter->off(null);

        $this->assertFalse($emitter->hasEventListeners(TestEvent::class));
        $this->assertFalse($emitter->hasEventListeners(TestOtherEvent::class));
        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals([], $actual);

        // Root class sender. off() on the class also drops instance listeners,
        // because both are stored against the class name.
        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'root-one';
        });

        Events::on($emitter, function(TestEvent $event) use ($emitter) {
            $this->assertEquals($event->sender, $emitter);
            return 'instance';
        });

        Events::on(RootEmitter::class, function(TestOtherEvent $event) {
            return 'root-two';
        });

        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertTrue(Events::hasListeners($emitter, TestEvent::class));
        $this->assertTrue($emitter->hasEventListeners(TestEvent::class));
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals(['root-one', 'instance'], $actual);

        $actual = Events::trigger(RootEmitter::class, new TestOtherEvent());
        $this->assertEquals(['root-two'], $actual);

        Events::off(RootEmitter::class, TestEvent::class);

        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse($emitter->hasEventListeners(TestEvent::class));
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = $emitter->testRoot();
        $this->assertEquals([], $actual);

        $actual = Events::trigger(RootEmitter::class, new TestEvent());
        $this->assertEquals([], $actual);

        $actual = Events::trigger(RootEmitter::class, new TestOtherEvent());
        $this->assertEquals(['root-two'], $actual);

        // Root class sender, explicit null event removes the rest.
        Events::on(RootEmitter::class, TestEvent::class, function() {
            return 'root-three';
        });

        Events::off(RootEmitter::class, null);

        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = Events::trigger(RootEmitter::class, new TestEvent());
        $this->assertEquals([], $actual);

        $actual = Events::trigger(RootEmitter::class, new TestOtherEvent());
        $this->assertEquals([], $actual);

        // A root listener and a subclass listener. Removing the root leaves
        // the subclass, and root emissions still reach that subclass.
        $root = new RootEmitter();
        $sub = new SubEmitter();

        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'root';
        });

        Events::on(SubEmitter::class, function(TestEvent $event) {
            return 'sub';
        });

        Events::on(SubEmitter::class, function(TestOtherEvent $event) {
            return 'sub-other';
        });

        $this->assertEquals(['root', 'sub'], $root->testRoot());
        $this->assertEquals(['root', 'sub'], $sub->testRoot());
        $this->assertEquals(['sub'], $sub->testSub());

        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertTrue(Events::hasListeners(SubEmitter::class, TestEvent::class));
        $this->assertTrue($root->hasEventListeners(TestEvent::class));
        $this->assertTrue($sub->hasEventListeners(TestEvent::class));

        Events::off(RootEmitter::class, TestEvent::class);

        $this->assertEquals(['sub'], $root->testRoot());
        $this->assertEquals(['sub'], $sub->testRoot());
        $this->assertEquals(['sub'], $sub->testSub());

        // The subclass listener still counts for the root sender.
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertTrue($root->hasEventListeners(TestEvent::class));
        $this->assertTrue(Events::hasListeners(SubEmitter::class, TestEvent::class));

        $actual = Events::trigger(SubEmitter::class, new TestOtherEvent());
        $this->assertEquals(['sub-other'], $actual);

        // Removing the subclass leaves the root listener. Subclass emissions
        // do not reach the root.
        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'root-again';
        });

        Events::off(SubEmitter::class, TestEvent::class);

        $this->assertEquals(['root-again'], $root->testRoot());
        $this->assertEquals(['root-again'], $sub->testRoot());
        $this->assertEquals([], $sub->testSub());

        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(SubEmitter::class, TestEvent::class));
        $this->assertFalse($sub->hasEventListeners(TestEvent::class));
        $this->assertTrue(Events::hasListeners(SubEmitter::class, TestOtherEvent::class));
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        Events::off(SubEmitter::class);

        $this->assertFalse(Events::hasListeners(SubEmitter::class, TestOtherEvent::class));
        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));

        $actual = Events::trigger(RootEmitter::class, new TestOtherEvent());
        $this->assertEquals([], $actual);

        $actual = $root->testRoot();
        $this->assertEquals(['root-again'], $actual);

        // Null sender removes one event from every sender.
        Events::on(OtherEmitter::class, function(TestEvent $event) {
            return 'other';
        });

        Events::on(RootEmitter::class, function(TestOtherEvent $event) {
            return 'root-other';
        });

        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertTrue(Events::hasListeners(OtherEmitter::class, TestEvent::class));
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = Events::trigger(RootEmitter::class, new TestEvent());
        $this->assertEquals(['root-again', 'other'], $actual);

        Events::off(null, TestEvent::class);

        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(SubEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(OtherEmitter::class, TestEvent::class));
        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = Events::trigger(RootEmitter::class, new TestEvent());
        $this->assertEquals([], $actual);

        $actual = Events::trigger(OtherEmitter::class, new TestEvent());
        $this->assertEquals([], $actual);

        $actual = Events::trigger(RootEmitter::class, new TestOtherEvent());
        $this->assertEquals(['root-other'], $actual);

        // Null sender with a null event removes every listener.
        Events::on(RootEmitter::class, function(TestEvent $event) {
            return 'root-restored';
        });

        Events::on(SubEmitter::class, function(TestEvent $event) {
            return 'sub-restored';
        });

        Events::off(null, null);

        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));
        $this->assertFalse(Events::hasListeners(SubEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(OtherEmitter::class, TestEvent::class));

        $actual = Events::trigger(RootEmitter::class, new TestEvent());
        $this->assertEquals([], $actual);

        $actual = Events::trigger(RootEmitter::class, new TestOtherEvent());
        $this->assertEquals([], $actual);

        // Omitted event with a null sender is the same clear.
        Events::on(LeafEmitter::class, function(TestEvent $event) {
            return 'leaf';
        });

        Events::on(OtherEmitter::class, function(TestOtherEvent $event) {
            return 'other-again';
        });

        $this->assertTrue(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertTrue(Events::hasListeners(OtherEmitter::class, TestOtherEvent::class));

        Events::off(null);

        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(LeafEmitter::class, TestEvent::class));
        $this->assertFalse(Events::hasListeners(OtherEmitter::class, TestOtherEvent::class));
        $this->assertFalse(Events::hasListeners(RootEmitter::class, TestOtherEvent::class));

        $actual = Events::trigger(LeafEmitter::class, new TestEvent());
        $this->assertEquals([], $actual);

        $actual = Events::trigger(OtherEmitter::class, new TestOtherEvent());
        $this->assertEquals([], $actual);
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
        $this->assertEquals(['root', 'sub', 'leaf1', 'leaf2', 'other'], $actual);

        // Partial tree of events.
        // from SUB to SUB + LEAF
        $count = 0;
        $actual = $emitter->testSub();
        $this->assertEquals(3, $count);
        $this->assertCount(3, $actual);
        $this->assertEquals(['sub', 'leaf1', 'leaf2'], $actual);

        // from LEAF to LEAF
        $count = 0;
        $actual = $emitter->testLeaf();
        $this->assertEquals(2, $count);
        $this->assertCount(2, $actual);
        $this->assertEquals(['leaf1', 'leaf2'], $actual);

        // from SELF (root) to LEAF
        $count = 0;
        $actual = $emitter->testSelf();
        $this->assertEquals(5, $count);
        $this->assertCount(5, $actual);
        $this->assertEquals(['root', 'sub', 'leaf1', 'leaf2', 'other'], $actual);

        // from THIS (leaf) to LEAF
        $count = 0;
        $actual = $emitter->testDynamic();
        $this->assertEquals(2, $count);
        $this->assertCount(2, $actual);
        $this->assertEquals(['leaf1', 'leaf2'], $actual);

        $emitter = new SubEmitter();

        // Dynamic events from SubEmitter will trigger for both sub and leaf.
        // from THIS (sub) to LEAF
        $count = 0;
        $actual = $emitter->testDynamic();
        $this->assertEquals(3, $count);
        $this->assertCount(3, $actual);
        $this->assertEquals(['sub', 'leaf1', 'leaf2'], $actual);
    }


    public function testLogs()
    {
        Events::setLogging(true);

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

        $this->assertCount(0, $logs[NullEmitter::class][TestEvent::class] ?? []);
        $this->assertCount(0, $logs[NullEmitter::class] ?? []);

        Events::clearLog();
        $logs = Events::getLogs();
        $this->assertCount(0, $logs);
    }


    public function testLogsFilter()
    {
        Events::setLogging(true);

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
        Events::setLogging(true);

        $this->testNested();

        $logs = Events::getLogs(['flatten' => true]);
        $this->assertCount(6, $logs);

        foreach ($logs as $item) {
            $this->assertStringStartsWith((string) floor(time() / 10), $item);
            $this->assertStringEndsWith('TestEvent', $item);
        }
    }


    /** @dataProvider dataWithLogs */
    public function testHasRun($withLogs)
    {
        Events::setLogging($withLogs);

        // Test specific parent/child scenario.
        Events::trigger(RootEmitter::class, new TestEvent());

        $actual = Events::hasRun(RootEmitter::class, TestEvent::class);
        $this->assertTrue($actual);

        $actual = Events::hasRun(SubEmitter::class, TestEvent::class);
        $this->assertTrue($actual);

        // Reset.
        Events::clearLog(true);

        $actual = Events::hasRun(RootEmitter::class, TestEvent::class);
        $this->assertFalse($actual);

        // Test lots of things.
        $this->testNested();

        $actual = Events::hasRun(RootEmitter::class, TestEvent::class);
        $this->assertTrue($actual);

        $actual = Events::hasRun(SubEmitter::class, TestEvent::class);
        $this->assertTrue($actual);

        $actual = Events::hasRun(LeafEmitter::class, TestEvent::class);
        $this->assertTrue($actual);

        // Extra event.
        $actual = Events::hasRun(RootEmitter::class, TestOtherEvent::class);
        $this->assertFalse($actual);

        $actual = Events::hasRun(LeafEmitter::class, TestOtherEvent::class);
        $this->assertFalse($actual);

        $event = new TestOtherEvent();
        Events::trigger(LeafEmitter::class, $event);

        $actual = Events::hasRun(RootEmitter::class, TestOtherEvent::class);
        $this->assertFalse($actual);

        $actual = Events::hasRun(LeafEmitter::class, TestOtherEvent::class);
        $this->assertTrue($actual);

        // Never ever triggered.
        $actual = Events::hasRun(NullEmitter::class, TestEvent::class);
        $this->assertFalse($actual);

        Events::clearLog(false);

        $logs = Events::getLogs();
        $this->assertCount(0, $logs);

        // Has log remains.
        $actual = Events::hasRun(RootEmitter::class, TestEvent::class);
        $this->assertTrue($actual);

        Events::clearLog(true);

        // Now empty.
        $actual = Events::hasRun(RootEmitter::class, TestEvent::class);
        $this->assertFalse($actual);
    }


    public function testTriggerOnce()
    {
        $count = 0;

        Events::on(RootEmitter::class, function(TestEvent $event) use (&$count) {
            $count++;
            return 'result';
        });

        $event = new TestEvent();

        // First trigger with once=true should fire.
        $actual = Events::trigger(RootEmitter::class, $event, true);
        $this->assertEquals(1, $count);
        $this->assertCount(1, $actual);
        $this->assertEquals(['result'], $actual);

        // Second trigger with once=true should NOT fire.
        $actual = Events::trigger(RootEmitter::class, $event, true);
        $this->assertEquals(1, $count);
        $this->assertCount(0, $actual);

        // Third trigger with once=true should still NOT fire.
        $actual = Events::trigger(RootEmitter::class, $event, true);
        $this->assertEquals(1, $count);
        $this->assertCount(0, $actual);

        // Trigger without once should still work.
        $actual = Events::trigger(RootEmitter::class, $event, false);
        $this->assertEquals(2, $count);
        $this->assertCount(1, $actual);
        $this->assertEquals(['result'], $actual);

        // Another once trigger should still NOT fire (already ran at least once).
        $actual = Events::trigger(RootEmitter::class, $event, true);
        $this->assertEquals(2, $count);
        $this->assertCount(0, $actual);
    }


    public function testEventSender()
    {
        $root1 = new RootEmitter();
        $root2 = new RootEmitter();

        $events = [];

        $root1->on(function(TestEvent $event) use (&$events) {
            $events[] = $event;
        });

        $root2->on(function(TestEvent $event) use (&$events) {
            $events[] = $event;
        });

        $root1->testDynamic();
        $root2->testDynamic();

        $this->assertCount(2, $events);

        $this->assertEquals(spl_object_id($root1), spl_object_id($events[0]->sender));
        $this->assertNotEquals(spl_object_id($root1), spl_object_id($events[1]->sender));

        $this->assertNotEquals(spl_object_id($root2), spl_object_id($events[0]->sender));
        $this->assertEquals(spl_object_id($root2), spl_object_id($events[1]->sender));
    }
}


class TestEvent extends Event {}
class TestOtherEvent extends Event {}


class EventsReset extends Events
{
    public static function reset()
    {
        Events::$_events = [];
        Events::clearLog(true);
        Events::setLogging(false);
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
        $results = $this->trigger(null, $event);
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


class NullEmitter
{
    use EventableTrait;
}
