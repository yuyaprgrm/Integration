<?php

namespace integration;

require __DIR__ . '/../../vendor/autoload.php';

use pocketmine\scheduler\AsyncTask;
use WebSocket\Client;

class AsyncPostMessageTask extends AsyncTask
{
    public function __construct(string $host, int $port, array $properties = [])
    {
        $this->host = $host;
        $this->port = $port;
        $message = [
            'player-name' => $properties['player-name'] ?? null,
            'message' => $properties['message'] ?? null,
            'uuid' => $properties['uuid'] ?? null,
            'server-name' => $properties['server-name'] ?? null
        ];
        $this->messageEncoded = json_encode($message);
        
    }

    public function onRun()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $client = new Client("ws://{$this->host}:{$this->port}/");
        $client->text($this->messageEncoded);
        $client->close();
    }
}