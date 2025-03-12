<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Loggable;
use karmabunny\kb\LoggerTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test loggable things.
 **/
class LoggableTest extends TestCase
{
    public function testLog()
    {
        $logs = [];
        $thing = new LogThing();

        $thing->addLogger(function($message, $level, $category) use (&$logs) {
            $logs[] = [$message, $level, $category];
        });

        // Standard log with level.
        $thing->log('hello', LOG_WARNING);

        $this->assertCount(1, $logs);
        $this->assertEquals(['hello', LOG_WARNING, LogThing::class], $logs[0]);

        // Double up, but ignoring the category.
        $thing->addLogger(function($message, $level) use (&$logs) {
            $logs[] = [$message, $level, 'override'];
        });

        // Log with a category, implicit INFO level.
        $thing->log('world', null, 'test');

        $this->assertCount(3, $logs);

        $this->assertEquals(['hello', LOG_WARNING, LogThing::class], $logs[0]);
        $this->assertEquals(['world', LOG_INFO, 'test'], $logs[1]);
        $this->assertEquals(['world', LOG_INFO, 'override'], $logs[2]);

        // No more loggers.
        $thing->clearLoggers();
        $thing->log('nothing at all');

        // Unchanged.
        $this->assertCount(3, $logs);
    }


    public function testAttach()
    {
        $thing = new LogThing();
        $other = new LogThing();

        $logs = [];

        $thing->attach($other);

        // Nothing yet.
        $thing->log('hello');
        $this->assertCount(0, $logs);


        $other->addLogger(function($message, $level, $category) use (&$logs) {
            $logs[] = [$message, $level, $category];
        });

        // Forwarded, yay.
        $thing->log('hello');
        $this->assertCount(1, $logs);
        $this->assertEquals(['hello', LOG_INFO, LogThing::class], $logs[0]);

        // Remove forwarded logger.
        $thing->clearLoggers();
        $thing->log('world');
        $this->assertCount(1, $logs);

        // Re-attach.
        $thing->attach($other);

        $thing->log('foo');
        $this->assertCount(2, $logs);
        $this->assertEquals(['hello', LOG_INFO, LogThing::class], $logs[0]);
        $this->assertEquals(['foo', LOG_INFO, LogThing::class], $logs[1]);

        // Remove actual logger.
        $other->clearLoggers();
        $thing->log('bar');
        $this->assertCount(2, $logs);
    }


    public function testFilteringLevel()
    {
        $thing = new LogThing();
        $other = new LogThing();

        $logs = [];

        $other->addLogger(function($message, $level, $category) use (&$logs) {
            $logs[] = [$message, $level, $category];
        });

        $thing->attach($other, LOG_INFO);
        $thing->log('hello', LOG_DEBUG);
        $this->assertCount(0, $logs);

        $thing->log('hello', LOG_INFO);
        $this->assertCount(1, $logs);

        $thing->log('hello', LOG_WARNING);
        $this->assertCount(2, $logs);

        $thing->log('hello', LOG_ERR);
        $this->assertCount(3, $logs);
    }


    public function testFilteringCategory()
    {
        $thing = new LogThing();
        $other = new LogThing();

        $logs = [];

        $other->addLogger(function($message, $level, $category) use (&$logs) {
            $logs[] = [$message, $level, $category];
        });

        // Simple filter.
        $thing->attach($other, null, 'test');
        $thing->log('hello', null);
        $this->assertCount(0, $logs);

        $other->log('hello', null, 'test');
        $this->assertCount(1, $logs);

        // Complex filter.
        $logs = [];
        $thing->clearLoggers();
        $thing->attach($other, null, [
            'doot' => true,
            'also',
        ]);

        $thing->log('hello', null, 'doot');
        $this->assertCount(1, $logs);

        $thing->log('hello', null, 'test');
        $this->assertCount(1, $logs);

        $thing->log('hello', null, 'also');
        $this->assertCount(2, $logs);

        // Logging directly without the filter.
        $other->log('hello', null, 'test');
        $this->assertCount(3, $logs);

        // Inverted filter.
        $logs = [];
        $thing->clearLoggers();
        $thing->attach($other, null, [
            'test' => false,
            'also' => false,
        ]);

        $thing->log('hello', null, 'doot');
        $this->assertCount(1, $logs);

        $thing->log('hello', null, 'test');
        $this->assertCount(1, $logs);

        $thing->log('hello', null, 'also');
        $this->assertCount(1, $logs);

        $thing->log('hello', null, 'blah');
        $this->assertCount(2, $logs);

        // Both filters, a bit odd.
        $logs = [];
        $thing->clearLoggers();
        $thing->attach($other, null, [
            'one' => false,
            'two' => false,
            'three' => true,
            'four' => true,
        ]);

        $thing->log('hello', null, 'one');
        $this->assertCount(0, $logs);

        $thing->log('hello', null, 'two');
        $this->assertCount(0, $logs);

        $thing->log('hello', null, 'three');
        $this->assertCount(1, $logs);

        $thing->log('hello', null, 'four');
        $this->assertCount(2, $logs);

        // Nothing here.
        $thing->log('hello', null, 'five');
        $this->assertCount(2, $logs);
    }
}


class LogThing implements Loggable
{
    use LoggerTrait;
}
