<?php

declare(strict_types=1);

namespace Logbook\Logbook\Commands;

use Illuminate\Console\Command;
use Logbook\Logbook\WebSocket\WebSocketServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

final class StartCommand extends Command
{
    protected $signature = 'logbook:start';

    protected $description = 'Start the Logbook server';

    public function handle(): void
    {
        $socket = new SocketServer('127.0.0.1:8345');

        $ws = new WebSocketServer();

        $http = new HttpServer(function (ServerRequestInterface $request) use ($ws): ResponseInterface {
            if ($request->getMethod() === 'GET' &&
                $request->getUri()->getPath() === '/ws') {
                return $ws->handle($request);
            }

            return Response::plaintext('The Logbook server is running.');
        });

        $http->listen($socket);

        $this->info('The Logbook server is running at 127.0.0.1:8345.');
    }
}
