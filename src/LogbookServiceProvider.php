<?php

declare(strict_types=1);

namespace Logbook\Logbook;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Logbook\Logbook\Commands\StartCommand;

final class LogbookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerCommands();
        $this->listenEvents();
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StartCommand::class,
            ]);
        }
    }

    private function listenEvents(): void
    {
        /** @var \Illuminate\Events\Dispatcher */
        $events = $this->app->get('events');

        $events->listen(MessageLogged::class, LogbookHandler::forLogs());
    }
}
