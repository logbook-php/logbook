<?php

declare(strict_types=1);

namespace Logbook\Logbook\Commands;

use Illuminate\Console\Command;
use Logbook\Logbook\WebSocket\WebSocketServer;
use React\Socket\SocketServer;

final class StartCommand extends Command
{
    protected $signature = 'logbook:start';

    protected $description = 'Start the Logbook server';

    public function handle(): void
    {
        $socket = new SocketServer('127.0.0.1:8345');

        $ws = new WebSocketServer();
        $ws->listen($socket);

        $this->info('The Logbook server is running at 127.0.0.1:8345.');
    }
}
