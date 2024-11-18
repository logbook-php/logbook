<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

use OutOfBoundsException;

final class Buffer
{
    private readonly int $length;

    private int $cursor;

    public function __construct(
        private readonly string $buffer
    ) {
        $this->length = strlen($buffer);
        $this->cursor = 0;
    }

    public function get(int $length = 1): string
    {
        $cursor = $this->cursor;

        $this->advance($length);

        return substr($this->buffer, $cursor, $length);
    }

    public function char(): int
    {
        return ord($this->get());
    }

    public function advance(int $length = 1): void
    {
        if ($this->cursor + $length > $this->length) {
            throw new OutOfBoundsException;
        }

        $this->cursor += $length;
    }

    public function cursor(): int
    {
        return $this->cursor;
    }

    public function length(): int
    {
        return $this->length;
    }
}
