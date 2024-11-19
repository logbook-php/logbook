<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use React\Socket\ConnectionInterface;
use Throwable;

final class HandshakeOpener
{
    /**
     * The magic GUID defined in RFC 6455.
     *
     * @see https://www.rfc-editor.org/rfc/rfc6455#section-1.3
     */
    const string GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * The WebSocket version that both the server and client communicate with.
     *
     * @see https://www.rfc-editor.org/rfc/rfc6455#section-4.2.1
     */
    const string WEBSOCKET_VERSION = '13';

    /**
     * Determines if the HTTP request attempts a WebSocket handshake.
     */
    public function attempt(string $message): bool
    {
        try {
            $request = Message::parseRequest($message);
        } catch (Throwable) {
            return false;
        }

        return $request->getMethod() === 'GET'
            && $request->getHeaderLine('Upgrade') === 'websocket'
            && $request->getHeaderLine('Connection') === 'Upgrade'
            && $request->getHeaderLine('Sec-WebSocket-Key') !== ''
            && $request->getHeaderLine('Sec-WebSocket-Version') !== '';
    }

    /**
     * Send the server's opening handshake.
     */
    public function respond(string $message, ConnectionInterface $connection): bool
    {
        $request = Message::parseRequest($message);

        if ($request->getHeaderLine('Sec-WebSocket-Version') != self::WEBSOCKET_VERSION) {
            $response = new Response(426, [
                'Sec-WebSocket-Version' => self::WEBSOCKET_VERSION,
            ]);

            $connection->write(Message::toString($response));

            return false;
        }

        $accept = base64_encode(hash('sha1',
            $request->getHeaderLine('Sec-WebSocket-Key').self::GUID,
            binary: true
        ));

        $response = new Response(101, [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $accept,
        ]);

        $connection->write(Message::toString($response));

        return true;
    }
}
