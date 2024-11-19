<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

use OutOfBoundsException;

final class BufferReader
{
    private readonly int $length;

    private int $cursor;

    public function __construct(
        private readonly string $buffer
    ) {
        $this->length = strlen($buffer);
        $this->cursor = 0;
    }

    /**
     * Get the buffer in length.
     */
    public function get(int $length = 1): string
    {
        $cursor = $this->cursor;

        $this->advance($length);

        return substr($this->buffer, $cursor, $length);
    }

    /**
     * Get a character from the buffer.
     */
    public function char(): int
    {
        return ord($this->get());
    }

    /**
     * Get an unsigned short integer (2 bytes) from the buffer.
     */
    public function unsignedShort(): int
    {
        return $this->char() << 8 | $this->char();
    }

    /**
     * Get an unsigned long long integer (8 bytes) from the buffer.
     */
    public function unsignedLongLong(): int
    {
        return $this->unsignedShort() << 48
            | $this->unsignedShort() << 32
            | $this->unsignedShort() << 16
            | $this->unsignedShort();
    }

    /**
     * Move the cursor forward.
     */
    private function advance(int $length = 1): void
    {
        if ($this->cursor + $length > $this->length) {
            throw new OutOfBoundsException;
        }

        $this->cursor += $length;
    }

    /**
     * The current cursor.
     */
    public function cursor(): int
    {
        return $this->cursor;
    }

    /**
     * The buffer length.
     */
    public function length(): int
    {
        return $this->length;
    }
}
