<?php

declare(strict_types=1);

namespace Logbook\Logbook\Tests\WebSocket;

use GuzzleHttp\Psr7\Message;
use Logbook\Logbook\WebSocket\HandshakeOpener;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use React\Http\Message\Request;
use React\Http\Message\Response;
use React\Socket\ConnectionInterface;

#[CoversClass(HandshakeOpener::class)]
final class HandshakeOpenerTest extends TestCase
{
    public function test_attempt_determines_the_handshake_request(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
        ]);
        $opener = new HandshakeOpener();
        $this->assertTrue($opener->attempt(Message::toString($request)));
    }

    /**
     * @param  array<string, string>  $headers
     */
    #[DataProvider('provide_upgrade_header_test_cases')]
    public function test_attempt_validates_upgrade_header(array $headers): void
    {
        $opener = new HandshakeOpener();
        $request1 = new Request('GET', 'http://example.com', [
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
            ...$headers,
        ]);
        $this->assertFalse($opener->attempt(Message::toString($request1)));
    }

    /**
     * @param  array<string, string>  $headers
     */
    #[DataProvider('provide_connection_header_test_cases')]
    public function test_attempt_validates_connection_header(array $headers): void
    {
        $opener = new HandshakeOpener();
        $request1 = new Request('GET', 'http://example.com', [
            'Upgrade' => 'websocket',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
            ...$headers,
        ]);
        $this->assertFalse($opener->attempt(Message::toString($request1)));
    }

    public function test_attempt_sec_websocket_key_header_must_be_provided(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => '',
            'Sec-WebSocket-Version' => '13',
        ]);
        $opener = new HandshakeOpener();
        $this->assertFalse($opener->attempt(Message::toString($request)));
    }

    public function test_attempt_sec_websocket_version_must_be_provided(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '',
        ]);
        $opener = new HandshakeOpener();
        $this->assertFalse($opener->attempt(Message::toString($request)));
    }

    public function test_respond_sends_server_opening_handshake(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
        ]);
        $opener = new HandshakeOpener();
        $mock = Mockery::mock(ConnectionInterface::class);
        $mock->shouldReceive('write')->once()->withArgs(function (string $message): bool {
            $response = Message::parseResponse($message);

            return $response->getStatusCode() === Response::STATUS_SWITCHING_PROTOCOLS
                && $response->getHeaderLine('Upgrade') === 'websocket'
                && $response->getHeaderLine('Connection') === 'Upgrade'
                && $response->getHeaderLine('Sec-WebSocket-Accept') === 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=';
        });
        $this->assertTrue($opener->respond(Message::toString($request), $mock));
    }

    public function test_respond_responds_upgrade_required_status_when_websocket_version_is_mismatched(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '12',
        ]);
        $opener = new HandshakeOpener();
        $mock = Mockery::mock(ConnectionInterface::class);
        $mock->shouldReceive('write')->once()->withArgs(function (string $message): bool {
            $response = Message::parseResponse($message);

            return $response->getStatusCode() === Response::STATUS_UPGRADE_REQUIRED
                && $response->getHeaderLine('Sec-WebSocket-Version') === '13';
        });
        $this->assertFalse($opener->respond(Message::toString($request), $mock));
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public static function provide_upgrade_header_test_cases(): array
    {
        return [
            'empty value' => [['Upgrade' => '']],
            'missing header' => [[]],
            'invalid value' => [['Upgrade' => 'foosocket']],
        ];
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public static function provide_connection_header_test_cases(): array
    {
        return [
            'empty value' => [['Connection' => '']],
            'missing header' => [[]],
            'invalid value' => [['Connection' => 'keep-alive']],
        ];
    }
}
