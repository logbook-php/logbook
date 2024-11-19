<?php

declare(strict_types=1);

namespace Logbook\Logbook\WebSocket;

use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use SplObjectStorage;

final class WebSocketServer
{
    private readonly HandshakeOpener $handshakeOpener;

    /**
     * @var \SplObjectStorage<\React\Socket\ConnectionInterface, null>
     */
    private readonly SplObjectStorage $pool;

    public function __construct()
    {
        $this->handshakeOpener = new HandshakeOpener();
        $this->pool = new SplObjectStorage();
    }

    public function listen(ServerInterface $server): void
    {
        $server->on('connection', function (ConnectionInterface $connection): void {
            $connection->on('data', fn (string $data) => $this->handleConnectionMessage($data, $connection));
            $connection->on('close', fn () => $this->handleConnectionClose($connection));
        });
    }

    private function handleConnectionMessage(string $data, ConnectionInterface $connection): void
    {
        if ($this->pool->contains($connection)) {
            return;
        }

        if ($this->handshakeOpener->attempt($data)) {
            if ($this->handshakeOpener->respond($data, $connection)) {
                $this->pool->attach($connection);

                return;
            }
        }

        $connection->close();
    }

    private function handleConnectionClose(ConnectionInterface $connection): void
    {
        $this->pool->detach($connection);
    }
}
