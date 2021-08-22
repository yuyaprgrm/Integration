<?php

namespace integration;

require_once __DIR__ . '/../../vendor/autoload.php';


use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\UUID;
use WebSocket\Client;
use WebSocket\ConnectionException;
use WebSocket\TimeoutException;

class AsyncGetMessageTask extends AsyncTask
{

    private string $host;
    private int    $port;
    private string $server_name;
    private string $server_uuid;

    private bool $closed;

    /**
     * @param array<string, string> $properties
     */
    public function __construct(string $host, int $port, array $properties = []) {
        $this->host = $host;
        $this->port = $port;
        $this->server_name = $properties['server-name'] ?? "Somewhere";
        $this->server_uuid = $properties['server-uuid'] ?? UUID::fromRandom()->toString();
        $this->closed = false;
    }

    public function onRun()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $client = new Client("ws://{$this->host}:{$this->port}/");
        $client->setTimeout(10);
        while(true)
        {
            try{
                $messageEncoded = $client->receive();
                $this->publishProgress($messageEncoded);
            }catch(TimeoutException $ex){
                if($this->closed)
                    break;
                
                $client = new Client("ws://{$this->host}:{$this->port}/");
                $client->setTimeout(10);
            }catch(ConnectionException $ex){
                break;
            }
        }
    }

    public function onCompletion(Server $server)
    {
        $server->getLogger()->warning('Connection with integration websocket has lost.');
    }

    public function onProgressUpdate(Server $server, $msg_encoded)
    {
        Main::getInstance()->broadcastMessage(json_decode($msg_encoded, true));
    }

    public function close(): void
    {
        $this->closed = true;
    }


}