<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

trait InterpretsFrame
{
    public function isContinuationFrame(): bool
    {
        return $this->opcode === 0x00;
    }

    public function isTextFrame(): bool
    {
        return $this->opcode === 0x01;
    }

    public function isBinaryFrame(): bool
    {
        return $this->opcode === 0x02;
    }

    public function isConnectionClose(): bool
    {
        return $this->opcode === 0x08;
    }

    public function isPing(): bool
    {
        return $this->opcode === 0x09;
    }

    public function isPong(): bool
    {
        return $this->opcode === 0x0a;
    }
}
