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
        return implode('', [
            $this->encodeFro(),
            $this->encodeMpl(),
            $this->encodeMaskingKey(),
            $this->encodePayload(),
        ]);
    }

    /**
     * Compose a byte from fin(1), rsv1(1), rsv2(1), rsv3(1) and opcode(4).
     */
    public function encodeFro(): string
    {
        $fin = (int) (bool) $this->frame->fin << 7;
        $rsv1 = (int) (bool) $this->frame->rsv1 << 6;
        $rsv2 = (int) (bool) $this->frame->rsv1 << 5;
        $rsv3 = (int) (bool) $this->frame->rsv1 << 4;
        $opcode = 0b1111 & $this->frame->opcode;

        return $this->char($fin | $rsv1 | $rsv2 | $rsv3 | $opcode);
    }

    /**
     * Compose a byte from mask(1) and payload len(7).
     */
    public function encodeMpl(): string
    {
        $mask = (int) ($this->frame->mask !== null) << 7;

        switch (true) {
            case $this->frame->length > 65535:
                return $this->char($mask | 127)
                    .$this->unsignedLongLong($this->frame->length);
            case $this->frame->length > 125:
                return $this->char($mask | 126)
                    .$this->unsignedShort($this->frame->length);
            default:
                return $this->char($mask | $this->frame->length);
        }
    }

    public function encodeMaskingKey(): string
    {
        if (! $this->frame->isMasked()) {
            return '';
        }

        return $this->frame->mask;
    }

    public function encodePayload(): string
    {
        if (! $this->frame->isMasked()) {
            return $this->frame->payload;
        }

        $payload = '';

        for ($i = 0; $i < $this->frame->length; $i++) {
            $payload .= $this->frame->payload[$i] ^ $this->frame->mask[$i % 4];
        }

        return $payload;
    }

    /**
     * Convert a codepoint to an ASCII character.
     */
    public function char(int $codepoint): string
    {
        return pack('C', $codepoint);
    }

    public function unsignedShort(int $value): string
    {
        return $this->char($value >> 8 & 0xff)
            .$this->char($value & 0xff);
    }

    public function unsignedLongLong(int $value): string
    {
        return $this->unsignedShort($value >> 48 & 0xffff)
            .$this->unsignedShort($value >> 32 & 0xffff)
            .$this->unsignedShort($value >> 16 & 0xffff)
            .$this->unsignedShort($value & 0xffff);
    }
}
