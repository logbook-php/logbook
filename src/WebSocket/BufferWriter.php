<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

final class BufferWriter
{
    private string $buffer = '';

    /**
     * Write a character into the buffer.
     */
    public function char(int $codepoint): self
    {
        $this->buffer .= pack('C', $codepoint);

        return $this;
    }

    /**
     * Write multiple characters into the buffer.
     */
    public function string(string $value): self
    {
        for ($i = 0; $i < strlen($value); $i++) {
            $this->char(ord($value[$i]));
        }

        return $this;
    }

    /**
     * Write an unsigned short integer (2 bytes) into the buffer.
     */
    public function unsignedShort(int $value): self
    {
        $this->char($value >> 8 & 0xff);

        return $this->char($value & 0xff);
    }

    /**
     * Write an unsigned long long integer (8 bytes) into the buffer.
     */
    public function unsignedLongLong(int $value): self
    {
        $this->unsignedShort($value >> 48 & 0xffff);
        $this->unsignedShort($value >> 32 & 0xffff);
        $this->unsignedShort($value >> 16 & 0xffff);

        return $this->unsignedShort($value & 0xffff);
    }

    /**
     * Get the buffer.
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Get the size of the buffer.
     */
    public function getLength(): int
    {
        return strlen($this->buffer);
    }
}
