<?php
// app/Console/Commands/WebSocketServer.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;
use App\WebSockets\WebSocketHandler;

class WebSocketServer extends Command
{
    protected $signature = 'websocket:init';
    protected $description = 'Start the WebSocket server';

    public function handle()
    {
        $this->info('Starting WebSocket server...');
        
        $loop = Factory::create();
        $webSocket = new WsServer(new WebSocketHandler());
        
        $server = new HttpServer($webSocket);
        
        // Create a socket
        $socket = new Server('0.0.0.0:6001', $loop);
        
        // Create the server
        $secureServer = new IoServer($server, $socket, $loop);
        
        $this->info('WebSocket server started on port 6001');
        
        $loop->run();
    }
}
