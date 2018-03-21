<?php

$server = new swoole_websocket_server("0.0.0.0", 9501);

$server->on('open', function (swoole_websocket_server $server, swoole_http_request $request) {
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (swoole_websocket_server $server, swoole_websocket_frame $frame) {
    foreach($server->connections as $key => $fd) {
        $user_message = $frame->data;
        $server->push($fd, $user_message);
    }

});

$server->on('close', function (swoole_websocket_server $server, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();