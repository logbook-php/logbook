<?php

declare(strict_types=1);

namespace Logbook\Logbook\Tests\WebSocket;

use Logbook\Logbook\WebSocket\HandshakeOpener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use React\Http\Message\Request;
use React\Http\Message\Response;

#[CoversClass(HandshakeOpener::class)]
final class HandshakeOpenerTest extends TestCase
{
    public function test_attempt_determines_the_handshake_request(): void
    {
        $request = new Request('GET', '/', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
        ]);
        $opener = new HandshakeOpener();
        $this->assertTrue($opener->attempt($request));
    }

    /**
     * @param  array<string, string>  $headers
     */
    #[DataProvider('provide_upgrade_header_test_cases')]
    public function test_attempt_validates_upgrade_header(array $headers): void
    {
        $opener = new HandshakeOpener();
        $request1 = new Request('GET', '/', [
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
            ...$headers,
        ]);
        $this->assertFalse($opener->attempt($request1));
    }

    /**
     * @param  array<string, string>  $headers
     */
    #[DataProvider('provide_connection_header_test_cases')]
    public function test_attempt_validates_connection_header(array $headers): void
    {
        $opener = new HandshakeOpener();
        $request1 = new Request('GET', '/', [
            'Upgrade' => 'websocket',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
            ...$headers,
        ]);
        $this->assertFalse($opener->attempt($request1));
    }

    public function test_attempt_sec_websocket_key_header_must_be_provided(): void
    {
        $request = new Request('GET', '/', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => '',
            'Sec-WebSocket-Version' => '13',
        ]);
        $opener = new HandshakeOpener();
        $this->assertFalse($opener->attempt($request));
    }

    public function test_attempt_sec_websocket_version_must_be_provided(): void
    {
        $request = new Request('GET', '/', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '',
        ]);
        $opener = new HandshakeOpener();
        $this->assertFalse($opener->attempt($request));
    }

    public function test_respond_sends_server_opening_handshake(): void
    {
        $request = new Request('GET', '/', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '13',
        ]);
        $opener = new HandshakeOpener();
        $response = $opener->respond($request);
        $this->assertSame(Response::STATUS_SWITCHING_PROTOCOLS, $response->getStatusCode());
        $this->assertSame('websocket', $response->getHeaderLine('Upgrade'));
        $this->assertSame('Upgrade', $response->getHeaderLine('Connection'));
        $this->assertSame('s3pPLMBiTxaQ9kYGzzhZRbK+xOo=', $response->getHeaderLine('Sec-WebSocket-Accept'));
    }

    public function test_respond_responds_upgrade_required_status_when_websocket_version_is_mismatched(): void
    {
        $request = new Request('GET', '/', [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Key' => 'dGhlIHNhbXBsZSBub25jZQ==',
            'Sec-WebSocket-Version' => '12',
        ]);
        $opener = new HandshakeOpener();
        $response = $opener->respond($request);
        $this->assertSame(Response::STATUS_UPGRADE_REQUIRED, $response->getStatusCode());
        $this->assertSame('13', $response->getHeaderLine('Sec-WebSocket-Version'));
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
