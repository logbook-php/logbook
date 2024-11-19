<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

final readonly class FrameEncoder
{
    public function __construct(
        public Frame $frame
    ) {
        //
    }

    public function encode(): string
    {
        $writer = new BufferWriter();

        $this->encodeFro($writer);
        $this->encodeMpl($writer);
        $this->encodeMaskingKey($writer);
        $this->encodePayload($writer);

        return $writer->getBuffer();
    }

    /**
     * Compose a byte from fin(1), rsv1(1), rsv2(1), rsv3(1) and opcode(4).
     */
    public function encodeFro(BufferWriter $buffer): void
    {
        $fin = (int) (bool) $this->frame->fin << 7;
        $rsv1 = (int) (bool) $this->frame->rsv1 << 6;
        $rsv2 = (int) (bool) $this->frame->rsv1 << 5;
        $rsv3 = (int) (bool) $this->frame->rsv1 << 4;
        $opcode = 0b1111 & $this->frame->opcode;

        $buffer->char($fin | $rsv1 | $rsv2 | $rsv3 | $opcode);
    }

    /**
     * Compose a byte from mask(1) and payload len(7).
     */
    public function encodeMpl(BufferWriter $buffer): void
    {
        $mask = (int) ($this->frame->mask !== null) << 7;

        match (true) {
            $this->frame->length > 65535 => $buffer->char($mask | 127)
                ->unsignedLongLong($this->frame->length),
            $this->frame->length > 125 => $buffer->char($mask | 126)
                ->unsignedShort($this->frame->length),
            default => $buffer->char($mask | $this->frame->length),
        };
    }

    public function encodeMaskingKey(BufferWriter $buffer): void
    {
        if (! $this->frame->isMasked()) {
            return;
        }

        $buffer->string($this->frame->mask);
    }

    public function encodePayload(BufferWriter $buffer): void
    {
        if (! $this->frame->isMasked()) {
            $buffer->string($this->frame->payload);

            return;
        }

        $masked = '';

        for ($i = 0; $i < $this->frame->length; $i++) {
            $masked .= $this->frame->payload[$i] ^ $this->frame->mask[$i % 4];
        }

        $buffer->string($masked);
    }
}
