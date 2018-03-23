<?php
define('MSG_CHAT_TYPE', 1);
define('MSG_NOTICE_TYPE', 2);

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
    $i = 0;

    foreach ($stat->fetchAll(PDO::FETCH_ASSOC) as $key => $row)
    {
        $userList[] = $row['nick_name'];
        $i++;
    }
    //通知所有用户 当前在线用户列表
    foreach ($server->connections as $fd)
    {
        $server->push($fd, json_encode(['type' => MSG_NOTICE_TYPE, 'qty' => $i, 'users' => $userList]));
    }
});

$server->on('message', function (swoole_websocket_server $server, swoole_websocket_frame $frame) {
    $dirName = dirname(__FILE__);
    require_once $dirName . '/../../common/pdo_mysql.php';
    $pdo = \common\pdo_mysql::db();
    $sql = 'SELECT * FROM swl_user where fd = :fd';
    $stat = $pdo->prepare($sql);
    $stat->execute([':fd' => $frame->fd]);
    $row = $stat->fetch(PDO::FETCH_ASSOC);
    if (empty($row))
        $nick_name = '匿名用户';
    else
        $nick_name = $row['nick_name'];

    foreach($server->connections as $key => $fd)
    {
        $server->push($fd, json_encode(['type' => MSG_CHAT_TYPE, 'nick_name' => $nick_name, 'msg' => $frame->data]));
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

    $sql = 'SELECT * FROM swl_user where fd > 0 ORDER BY fd ASC';
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
        $server->push($fd, json_encode(['type' => MSG_NOTICE_TYPE, 'qty' => $i, 'users' => $userList]));
    }
});

$server->start();