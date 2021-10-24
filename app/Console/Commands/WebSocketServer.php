<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Http\Controllers\QRLoginWS\QRLoginWSController as WebSocketController;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializing Websocket server to receive and manage connections';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->forlocal();
        // $this->forprodserver();
    }
    
    
    public function forlocal()
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new WebSocketController()
                )
            ),
            8090
        );
        $server->run();
    }

    public function forprodserver()
    {
        $loop = Factory::create();
        $webSock = new SecureServer(new Server('0.0.0.0:8090', $loop) , $loop, array(
            'local_cert' => '/etc/letsencrypt/live/test.tv.com/fullchain.pem', // path to your cert
            'local_pk' => '/etc/letsencrypt/live/test.tv.com/privkey.pem', // path to your server private key
            'allow_self_signed' => true, // Allow self signed certs (should be false in production)
            'verify_peer' => false
        ));
        // Ratchet magic
        $webServer = new IoServer(new HttpServer(new WsServer(new WebSocketController())) , $webSock);
        $loop->run();
    }
}
