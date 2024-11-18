<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

final readonly class Frame
{
    use InterpretsFrame;

    public function __construct(
        public int $fin,
        public int $rsv1,
        public int $rsv2,
        public int $rsv3,
        public int $opcode,
        public ?string $mask,
        public int $length,
        public string $payload
    ) {
        //
    }

    public static function parse(string $message): static
    {
        $decoder = new FrameDecoder();

        return $decoder->decode($message);
    }

    public function isFinalFragment(): bool
    {
        return $this->fin === 1;
    }

    /**
     * @phpstan-assert-if-true string $this->mask
     */
    public function isMasked(): bool
    {
        return is_string($this->mask);
    }

    public function toBuffer(): string
    {
        $encoder = new FrameEncoder($this);

        return $encoder->encode();
    }
}
