<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

final class FrameDecoder
{
    public function decode(string $message): Frame
    {
        $buffer = new BufferReader($message);

        [$fin, $rsv1, $rsv2, $rsv3, $opcode] = $this->decodeFro($buffer->char());
        [$mask, $len] = $this->decodeMpl($buffer);
        $maskingKey = $mask === 1 ? $this->decodeMaskingKey($buffer) : null;
        $payload = $this->decodePayload($buffer->get($len), $maskingKey);

        return new Frame(
            $fin, $rsv1, $rsv2, $rsv3, $opcode,
            $maskingKey, $len, $payload
        );
    }

    /**
     * Decode a byte includes fin(1), rsv1(1), rsv2(2), rsv3(1) and opcode(4).
     *
     * @return array<int, int>
     */
    public function decodeFro(int $byte): array
    {
        $fin = ($byte & 0b1000_0000) >> 7;
        $rsv1 = ($byte & 0b0100_0000) >> 6;
        $rsv2 = ($byte & 0b0010_0000) >> 5;
        $rsv3 = ($byte & 0b0001_0000) >> 4;
        $opcode = $byte & 0b1111;

        return [$fin, $rsv1, $rsv2, $rsv3, $opcode];
    }

    /**
     * Decode a byte includes mask(1) and payload length(7).
     *
     * @return array<int, int>
     */
    public function decodeMpl(BufferReader $buffer): array
    {
        $byte = $buffer->char();

        $mask = ($byte & 0b1000_0000) >> 7;
        $len = $byte & 0b0111_1111;

        // A 7-bit field represents the payload length. However, if the length
        // exceeds the 7-bit boundary, we use a convention to indicate the
        // actual size. A length value of 126 signifies that the next 2
        // bytes contain the true payload size. Similarly, a length
        // value of 127 indicates that the subsequent 8 bytes
        // represent the payload size.
        $len = match ($len) {
            126 => $buffer->unsignedShort(),
            127 => $buffer->unsignedLongLong(),
            default => $len,
        };

        return [$mask, $len];
    }

    public function decodeMaskingKey(BufferReader $buffer): string
    {
        return $buffer->get(4);
    }

    public function decodePayload(string $payload, ?string $maskingKey = null): string
    {
        if ($maskingKey === null) {
            return $payload;
        }

        $unmasked = '';

        for ($i = 0; $i < strlen($payload); $i++) {
            $unmasked .= $payload[$i] ^ $maskingKey[$i % 4];
        }

        return $unmasked;
    }
}
