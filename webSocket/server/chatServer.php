<?php

$server = new swoole_websocket_server("0.0.0.0", 9501);


$server->on('open', function (swoole_websocket_server $server, swoole_http_request $request) {
    echo "server: handshake success with fd{$request->fd}\n";
    $dirName = dirname(__FILE__);
    require_once $dirName . '/../../common/pdo_mysql.php';
    $pdo = \common\pdo_mysql::db();
    $sql = 'UPDATE swl_user set fd = :fd WHERE nick_name = :nick_name';
    $stat = $pdo->prepare($sql);
    $stat->execute([':fd' => $request->fd, ':nick_name' => $request->get['nick_name']]);
    if ($stat->rowCount() < 0)
    {
        echo "fd{$request->fd} bind failed\n";
    }

    $sql = 'SELECT * FROM swl_user where fd > 0';
    $stat = $pdo->query($sql);
    $userList = [];
    $i = 1;

    foreach ($stat->fetchAll(PDO::FETCH_ASSOC) as $key => $row)
    {
        $userList[] = $row['nick_name'];
        $i++;
    }
    //通知所有用户 当前在线用户列表
    foreach ($server->connections as $fd)
    {
        $server->push($fd, json_encode(['type' => 4, 'qty' => $i, 'users' => $userList]));
    }
});

$server->on('message', function (swoole_websocket_server $server, swoole_websocket_frame $frame) {
    foreach($server->connections as $key => $fd)
    {
        $user_message = $frame->data;
        $server->push($fd, json_encode(['type' => 3, 'msg' => $user_message]));
    }

});

$server->on('close', function (swoole_websocket_server $server, $fd) {
    echo "client {$fd} closed\n";
    $dirName = dirname(__FILE__);

    require_once $dirName . '/../../common/pdo_mysql.php';
    $pdo = \common\pdo_mysql::db();
    $sql = 'UPDATE swl_user set fd = 0 WHERE fd = :fd';
    $stat = $pdo->prepare($sql);
    $stat->execute([':fd' => $fd]);
    if ($stat->rowCount() < 0)
    {
        echo "fd{$fd} unbind failed\n";
    }

    $sql = 'SELECT * FROM swl_user where fd > 0';
    $stat = $pdo->query($sql);
    $userList = [];
    $i = 1;

    foreach ($stat->fetchAll(PDO::FETCH_ASSOC) as $key => $row)
    {
        $userList[] = $row['nick_name'];
        $i++;
    }
    //通知所有用户 当前在线用户列表
    foreach ($server->connections as $fd)
    {
        $server->push($fd, json_encode(['type' => 4, 'qty' => $i, 'users' => $userList]));
    }
});

$server->start();