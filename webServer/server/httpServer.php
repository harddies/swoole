<?php
/**
 * Created by PhpStorm.
 * User: Ronnie
 * Date: 2018/03/21
 * Time: 09:39
 */


$http = new swoole_http_server("0.0.0.0", 9502);
$http->on('request', function (swoole_http_request $request, swoole_http_response $response) {
    $dirName = dirname(__FILE__);

    if (isset($request->post['act']) && $request->post['act'] == 'login')
    {
        if (empty($request->post['nick_name']) || empty($request->post['password']))
        {
            $response->header("Content-Type", "application/json; charset=utf-8");
            $response->end(json_encode(['error' => true, 'msg' => '请输入用户名密码']));
        }

        $nick_name = $request->post['nick_name'];
        $password = md5($request->post['nick_name']);

        $db_config = require_once $dirName . '/../../config/db.php';

        $pdo = new PDO($db_config['mysql']['connectionString'], $db_config['mysql']['username'], $db_config['mysql']['password'], ['charset' => 'utf8']);
        $sql = 'SELECT * FROM swl_user WHERE nick_name = :nick_name AND password = :password';
        $stat = $pdo->prepare($sql);
        $stat->execute(
            [
                ':nick_name' => $nick_name,
                ':password' => $password
            ]
        );
        $row = $stat->fetch(PDO::FETCH_ASSOC);
        if (!empty($row))
        {
            $response->header("Content-Type", "application/json; charset=utf-8");
            $response->end(json_encode(['error' => false, 'msg' => $row['nick_name']]));
        }
        else
        {
            $sql = 'INSERT INTO swl_user (password, nick_name) VALUES (:password, :nick_name)';
            $stat = $pdo->prepare($sql);
            $stat->execute(
                [
                    ':nick_name' => $nick_name,
                    ':password' => $password
                ]
            );
            if ($stat->rowCount() > 0)
            {
                $response->header("Content-Type", "application/json; charset=utf-8");
                $response->end(json_encode(['error' => false, 'msg' => $row['nick_name']]));
            }
            else
            {
                $response->header("Content-Type", "application/json; charset=utf-8");
                $response->end(json_encode(['error' => true, 'msg' => '注册失败']));
            }
        }
    }
    else
    {
        $response->header("Content-Type", "text/html; charset=utf-8");
        $response->end(file_get_contents($dirName . '/../../webSocket/client/chat.html'));
    }
});
$http->start();