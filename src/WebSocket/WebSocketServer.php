<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;

final readonly class WebSocketServer
{
    private HandshakeOpener $handshakeOpener;

    public function __construct()
    {
        $this->handshakeOpener = new HandshakeOpener();
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($this->handshakeOpener->attempt($request)) {
            return $this->handshakeOpener->respond($request);
        }

        return new Response();
    }
}
