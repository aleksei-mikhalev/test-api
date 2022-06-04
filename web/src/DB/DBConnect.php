<?php

namespace App\DB;

use PDO;

/*
 * Данные для подключения лучше вынести в отдельный config-файл, чтобы не хранить логин и пароль в общем коде.
 * Но оставим здесь для простоты.
 */
define('DB_TYPE', 'mysql');
define('DB_NAME', 'api');
define('DB_HOST', 'api_db');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '1111');
define('DB_CHAR', 'utf8');

class DBConnect
{
    public static $instance;

    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            $dsn = DB_TYPE . ':dbname=' . DB_NAME . ';port=' . DB_PORT . ';host=' . DB_HOST . ';charset=' . DB_CHAR;
            self::$instance = new PDO($dsn, DB_USER, DB_PASS);
        }

        return self::$instance;
    }

    private function __construct() {}

    private function __clone() {}

    public function __wakeup() {}
}
