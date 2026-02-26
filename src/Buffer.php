<?php
declare(strict_types=1);
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

use RuntimeException;

/**
 * A wrapper for the PHP output buffer.
 *
 * This naturally flushes the buffer contents when destroyed.
 */
class Buffer
{

    private int $buffer_level = 0;


    /**
     * Open this buffer.
     *
     * @param null|callable $callback
     * @param int $chunk_size
     * @param int $flags
     * @return bool true on success, false on failure
     * @throws RuntimeException if already open
     */
    public function start(?callable $callback = null, int $chunk_size = 0, int $flags = PHP_OUTPUT_HANDLER_STDFLAGS): bool
    {
        if ($this->level() > 0) {
            throw new RuntimeException('Buffer is already open');
        }

        $ok = ob_start($callback, $chunk_size, $flags);
        $this->buffer_level = ob_get_level();
        return $ok;
    }


    /** @inheritdoc */
    public function __destruct()
    {
        $this->end(true);
    }


    /**
     * Get the nested level of this buffer.
     *
     * @return int
     */
    public function level(): int
    {
        if ($this->buffer_level == 0) {
            return 0;
        }

        // Someone else closed our buffer.
        if (ob_get_level() == 0) {
            $this->buffer_level = 0;
            return 0;
        }

        return $this->buffer_level;
    }


    /**
     * Flush this buffer.
     *
     * "Flushing" means to send content to the parent buffer. Content is only
     * sent once there are no more buffers to flush.
     *
     * This closes all child buffers.
     *
     * @param bool $send don't send, just merge child buffers into this one
     * @return void
     */
    public function flush(bool $send = true)
    {
        if (!$this->level()) {
            return;
        }

        while (ob_get_level() > $this->buffer_level) {
            ob_end_flush();
        }

        if ($send and ob_get_level() == $this->buffer_level) {
            ob_flush();
        }
    }


    /**
     * Discard the buffer contents.
     *
     * This closes all child buffers.
     *
     * @return void
     */
    public function discard()
    {
        if (!$this->level()) {
            return;
        }

        while (ob_get_level() > $this->buffer_level) {
            ob_end_clean();
        }

        if (ob_get_level() == $this->buffer_level) {
            ob_clean();
        }
    }


    /**
     * Get the contents without clearing it.
     *
     * This closes all child buffers.
     *
     * @return string
     */
    public function contents(): string
    {
        if (!$this->level()) {
            return '' ;
        }

        $this->flush(false);

        if (ob_get_level() == $this->buffer_level) {
            return ob_get_contents() ?: '';
        }

        return '';
    }


    /**
     * Clean and return the contents, including all child buffers.
     *
     * This closes all child buffers.
     *
     * @return string
     */
    public function clean(): string
    {
        if (!$this->level()) {
            return '';
        }

        $contents = $this->contents();
        ob_clean();
        return $contents;
    }


    /**
     * Close this buffer (and all child buffers) and return the contents.
     *
     * This closes all child buffers.
     *
     * @return string
     */
    public function close(): string
    {
        if (!$this->level()) {
            return '';
        }

        $this->flush(false);

        if (ob_get_level() == $this->buffer_level) {
            $this->buffer_level = 0;
            return ob_get_clean() ?: '';
        }

        return '';
    }


    /**
     * Close this buffer and all child buffers.
     *
     * Flush or discard contents, including all child buffers.
     *
     * @return void
     */
    public function end(bool $flush = true): void
    {
        if (!$this->level()) {
            return;
        }

        if ($flush) {
            while (ob_get_level() > $this->buffer_level) {
                ob_end_flush();
            }

            if (ob_get_level() == $this->buffer_level) {
                ob_end_flush();
            }
        } else {
            while (ob_get_level() > $this->buffer_level) {
                ob_end_clean();
            }

            if (ob_get_level() == $this->buffer_level) {
                ob_end_clean();
            }
        }

        $this->buffer_level = 0;
    }


    /**
     * Discard all buffers.
     *
     * @return void
     */
    public static function closeAll()
    {
        while (ob_get_level()) ob_end_clean();
    }


    /**
     * Dump all buffers into the output.
     *
     * @return void
     */
    public static function flushAll()
    {
        while (ob_get_level()) ob_end_flush();
    }
}
