<?php

declare(strict_types=1);

namespace Logbook\Logbook;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

final class Logbook
{
    private static ?Logbook $instance = null;

    public function __construct(
        private readonly ClientInterface $client
    ) {
        //
    }

    public static function getInstance(): static
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        $client = new Client([
            'base_uri' => 'http://127.0.0.1:8345',
            'timeout' => 2.0,
        ]);

        return static::$instance = new static($client);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function write(string $eventName, array $data): void
    {
        $request = new Request('POST', '/write', [], \json_encode([
            'event' => $eventName,
            'data' => $data,
        ], JSON_THROW_ON_ERROR));

        $this->client->sendRequest($request);
    }
}
