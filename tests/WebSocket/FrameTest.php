<?php

declare(strict_types=1);

namespace Logbook\Logbook\Tests\WebSocket;

use Logbook\Logbook\WebSocket\BufferReader;
use Logbook\Logbook\WebSocket\BufferWriter;
use Logbook\Logbook\WebSocket\Frame;
use Logbook\Logbook\WebSocket\FrameDecoder;
use Logbook\Logbook\WebSocket\FrameEncoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(Frame::class)]
#[CoversClass(FrameEncoder::class)]
#[CoversClass(FrameDecoder::class)]
#[CoversClass(BufferWriter::class)]
#[CoversClass(BufferReader::class)]
final class FrameTest extends TestCase
{
    public function test_parse_and_build_single_frame_unmasked_text_message(): void
    {
        $message = hex2bin('810548656c6c6f') ?: throw new UnexpectedValueException;
        $frame = Frame::parse($message);
        $this->assertSame(1, $frame->fin);
        $this->assertTrue($frame->isFinalFragment());
        $this->assertSame(0, $frame->rsv1);
        $this->assertSame(0, $frame->rsv2);
        $this->assertSame(0, $frame->rsv3);
        $this->assertSame(0x01, $frame->opcode);
        $this->assertTrue($frame->isTextFrame());
        $this->assertNull($frame->mask);
        $this->assertFalse($frame->isMasked());
        $this->assertSame(5, $frame->length);
        $this->assertSame('Hello', $frame->payload);
        $this->assertSame($message, $frame->toBuffer());
    }

    public function test_parse_and_build_single_frame_masked_text_message(): void
    {
        $message = hex2bin('818537fa213d7f9f4d5158') ?: throw new UnexpectedValueException;
        $frame = Frame::parse($message);
        $this->assertSame(1, $frame->fin);
        $this->assertTrue($frame->isFinalFragment());
        $this->assertSame(0, $frame->rsv1);
        $this->assertSame(0, $frame->rsv2);
        $this->assertSame(0, $frame->rsv3);
        $this->assertSame(0x01, $frame->opcode);
        $this->assertTrue($frame->isTextFrame());
        $this->assertSame('37fa213d', bin2hex($frame->mask ?? ''));
        $this->assertTrue($frame->isMasked());
        $this->assertSame(5, $frame->length);
        $this->assertSame('Hello', $frame->payload);
        $this->assertSame($message, $frame->toBuffer());
    }

    public function test_parse_and_build_single_frame_unmasked_binary_message_256_bytes(): void
    {
        $message = hex2bin('827e0100'.str_repeat('6f', 256)) ?: throw new UnexpectedValueException;
        $frame = Frame::parse($message);
        $this->assertSame(1, $frame->fin);
        $this->assertSame(0, $frame->rsv1);
        $this->assertSame(0, $frame->rsv2);
        $this->assertSame(0, $frame->rsv3);
        $this->assertSame(0x02, $frame->opcode);
        $this->assertTrue($frame->isBinaryFrame());
        $this->assertFalse($frame->isMasked());
        $this->assertSame(256, $frame->length);
        $this->assertSame(str_repeat('o', 256), $frame->payload);
        $this->assertSame($message, $frame->toBuffer());
    }

    public function test_parse_and_build_single_frame_unmasked_binary_message_64kb(): void
    {
        $message = hex2bin('827f0000000000010000'.str_repeat('6f', 65536)) ?: throw new UnexpectedValueException;
        $frame = Frame::parse($message);
        $this->assertSame(1, $frame->fin);
        $this->assertSame(0, $frame->rsv1);
        $this->assertSame(0, $frame->rsv2);
        $this->assertSame(0, $frame->rsv3);
        $this->assertSame(0x02, $frame->opcode);
        $this->assertTrue($frame->isBinaryFrame());
        $this->assertFalse($frame->isMasked());
        $this->assertSame(65536, $frame->length);
        $this->assertSame(str_repeat('o', 65536), $frame->payload);
        $this->assertSame($message, $frame->toBuffer());
    }
}
