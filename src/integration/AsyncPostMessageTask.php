<?php

namespace integration;



use Exception;
use pocketmine\scheduler\AsyncTask;
use WebSocket\Client;

class AsyncPostMessageTask extends AsyncTask
{

    private string $host;
    private int    $port;
    
    private string $messageEncoded;

    /**
     * @param array<string, string> $properties
     */
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
        if($messageEncoded = json_encode($message))
        {
            $this->messageEncoded = $messageEncoded;
        }else{
            throw new \Exception('Failed To Convert to Json');
        }
    }

    public function onRun()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $client = new Client("ws://{$this->host}:{$this->port}/");
        $client->text($this->messageEncoded);
        $client->close();
    }
}