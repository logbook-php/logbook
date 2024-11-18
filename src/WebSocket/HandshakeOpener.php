<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;

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
    public function attempt(RequestInterface $request): bool
    {
        return $request->getHeaderLine('Upgrade') === 'websocket'
            && $request->getHeaderLine('Connection') === 'Upgrade'
            && $request->getHeaderLine('Sec-WebSocket-Key') !== ''
            && $request->getHeaderLine('Sec-WebSocket-Version') !== '';
    }

    /**
     * Send the server's opening handshake.
     */
    public function respond(RequestInterface $request): ResponseInterface
    {
        if ($request->getHeaderLine('Sec-WebSocket-Version') != self::WEBSOCKET_VERSION) {
            return (new Response(Response::STATUS_UPGRADE_REQUIRED))
                ->withHeader('Sec-WebSocket-Version', self::WEBSOCKET_VERSION);
        }

        $accept = base64_encode(hash('sha1',
            $request->getHeaderLine('Sec-WebSocket-Key').self::GUID,
            binary: true
        ));

        return (new Response(Response::STATUS_SWITCHING_PROTOCOLS))
            ->withHeader('Upgrade', 'websocket')
            ->withHeader('Connection', 'Upgrade')
            ->withHeader('Sec-WebSocket-Accept', $accept);
    }
}
