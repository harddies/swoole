<?php
/**
 * pdo_mysql.php.
 * User: Ronnie
 * Date: 2018/03/22
 * Time: 23:25
 */

namespace common;

class pdo_mysql
{
    private static $_db = null;

    private function __construct()
    {

    }

    /**
     * @return \PDO
     */
    public static function db()
    {
        if (self::$_db instanceof \PDO)
        {
            return self::$_db;
        }
        else
        {
            $db_config = require_once dirname(__FILE__) . '/../config/db.php';

            return self::$_db = new \PDO($db_config['mysql']['connectionString'], $db_config['mysql']['username'], $db_config['mysql']['password'], ['charset' => 'utf8']);
        }
    }
}