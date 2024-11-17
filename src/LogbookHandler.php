<?php

declare(strict_types=1);

namespace Logbook\Logbook;

use Closure;
use Illuminate\Log\Events\MessageLogged;

final class LogbookHandler
{
    /**
     * @return \Closure(\Illuminate\Log\Events\MessageLogged): void
     */
    public static function forLogs(): Closure
    {
        return function (MessageLogged $event): void {
            Logbook::getInstance()->write('log', [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ]);
        };
    }
}
