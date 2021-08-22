<?php

namespace integration\websocket;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Chat;

if(!isset($argv[0])) exit("no args");

$port = (int) $argv[1];
    require __DIR__ . '/../../../vendor/autoload.php';

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new InternalWebSocketServer()
            )
        ),
        $port
    );

    $server->run();