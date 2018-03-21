<?php
/**
 * Created by PhpStorm.
 * User: Ronnie
 * Date: 2018/03/21
 * Time: 09:39
 */

$http = new swoole_http_server("0.0.0.0", 9502);
$http->on('request', function (swoole_http_request $request, swoole_http_response $response) {
    var_dump($request->get, $request->post);
    $response->header("Content-Type", "text/html; charset=utf-8");
    $response->end(file_get_contents(dirname(__FILE__) . '/../../webSocket/client/chat.html'));
});
$http->start();