<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

use karmabunny\kb\Buffer;
use PHPUnit\Framework\TestCase;

/**
 * Test the Buffer helper class.
 */
final class BufferTest extends TestCase
{

    /**
     * Initial buffer level before creating the temporary buffer.
     *
     * @var int
     */
    private $temp_level = 0;


    /**
     * Clean up any existing output buffers before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create a temporary buffer to isolate our test buffers from PHPUnit.
        ob_start();
        $this->temp_level = ob_get_level();
    }


    /**
     * Clean up any existing output buffers after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up our buffers.
        while (ob_get_level() >= $this->temp_level) {
            ob_end_clean();
        }
    }


    public function testConstructor()
    {
        $buffer = new Buffer();
        $this->assertEquals($this->temp_level + 1, ob_get_level());
        $buffer->end();
    }


    public function testEndWithFlush()
    {
        $buffer = new Buffer();

        echo 'end flush';
        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        $buffer->end(true);

        // Buffer should be closed.
        $this->assertEquals($this->temp_level, ob_get_level());
        $contents = ob_get_contents();
        $this->assertEquals('end flushchild1child2', $contents);
    }


    public function testEndWithoutFlush()
    {
        $buffer = new Buffer();
        echo 'end discard';

        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        $buffer->end(false);

        // Buffer should be closed.
        $this->assertEquals($this->temp_level, ob_get_level());
        $contents = ob_get_contents();
        $this->assertEquals('', $contents);
    }


    public function testFlushWithSend()
    {
        $buffer = new Buffer();

        echo 'content';
        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        $buffer->flush(true);

        // Buffer should still exist (flush doesn't close it).
        $this->assertEquals($this->temp_level + 1, ob_get_level());

        // Content should have been flushed, so buffer should be empty.
        $contents = $buffer->contents();
        $this->assertEquals('', $contents);

        $buffer->end(false);

        // Contents are in the parent/temp buffer.
        $this->assertEquals('contentchild1child2', ob_get_contents());
    }


    public function testFlushWithoutSend()
    {
        $buffer = new Buffer();

        echo 'content';
        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        $buffer->flush(false);

        // Buffer should still exist.
        $this->assertEquals($this->temp_level + 1, ob_get_level());

        $contents = $buffer->contents();
        $this->assertEquals('contentchild1child2', $contents);

        $buffer->end(false);

        // Contents were not flushed, so not in the buffer.
        $this->assertEmpty(ob_get_contents());
    }


    public function testDiscard()
    {
        $buffer = new Buffer();

        echo 'parent';
        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        $buffer->discard();

        // All child buffers should be closed, buffer itself remains but is empty.
        $this->assertEquals($this->temp_level + 1, ob_get_level());

        $this->assertEmpty($buffer->contents());

        $buffer->end(true);

        $this->assertEmpty(ob_get_contents());
    }


    public function testContents()
    {
        $buffer = new Buffer();

        echo 'parent';

        $contents = $buffer->contents();
        $this->assertEquals('parent', $contents);

        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        $contents = $buffer->contents();

        // Should include all child buffers.
        $this->assertEquals('parentchild1child2', $contents);

        // Child buffers should be closed, only temp buffer and our buffer remain.
        $this->assertEquals($this->temp_level + 1, ob_get_level());
        $buffer->end();
    }


    public function testClean()
    {
        $buffer = new Buffer();

        echo 'parent';

        $contents = $buffer->clean();
        $this->assertEquals('parent', $contents);
        $this->assertEquals($this->temp_level + 1, ob_get_level());

        echo 'other';

        ob_start();
        echo 'child1';
        ob_start();
        echo 'child2';

        // Should include all child buffers.
        $contents = $buffer->clean();
        $this->assertEquals('otherchild1child2', $contents);
        $this->assertEquals($this->temp_level + 1, ob_get_level());
    }


    public function testMultipleBuffers()
    {
        $buffer1 = new Buffer();
        echo 'buffer1';

        $buffer2 = new Buffer();
        echo 'buffer2';

        $buffer3 = new Buffer();
        echo 'buffer3';

        // Should have 3 nested buffers.
        $this->assertEquals($this->temp_level + 3, ob_get_level());

        // Close inner buffer.
        $contents3 = $buffer3->close();
        $this->assertEquals('buffer3', $contents3);
        $this->assertEquals($this->temp_level + 2, ob_get_level());

        // Close middle buffer.
        $contents2 = $buffer2->close();
        $this->assertEquals('buffer2', $contents2);
        $this->assertEquals($this->temp_level + 1, ob_get_level());

        // Close outer buffer.
        $contents1 = $buffer1->close();
        $this->assertEquals('buffer1', $contents1);
        $this->assertEquals($this->temp_level, ob_get_level());
    }


    public function testEmptyBuffer()
    {
        $buffer = new Buffer();
        $contents = $buffer->contents();
        $this->assertEquals('', $contents);

        $clean = $buffer->close();
        $this->assertEquals('', $clean);
    }

}
