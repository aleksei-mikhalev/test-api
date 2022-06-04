<?php

namespace App\API;

use App\Logger\LoggerDB;

class Api
{
    public $apiClass = ''; // Класс API для работы. В нашем случае есть только класс баланса кошелька: \api\balance (файл: Balance.php)
    public $requestUri = [];
    public $requestParams = [];
    protected $action = ''; // Конкретный метод в классе. Например: \api\balance\get (получить текущий баланс)
    protected $method = '';

    public function __construct()
    {
        header("Content-Type: application/json");

        // Получаем части адреса для дальнейшего роутинга
        $this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
        $this->requestParams = $_REQUEST;

        // Определение метода запроса (GET, POST ...)
        $this->method = $_SERVER['REQUEST_METHOD'];

        $this->run();
    }

    public function run()
    {
        // Проверяем наличие вызываемого класса
        $className =  '\App\API\\'.ucwords(array_shift($this->requestUri), '_');
        if (class_exists($className)) {
            $action = array_shift($this->requestUri);
            $objClass = new $className($this->method, $this->requestUri, $this->requestParams, new LoggerDB());

            // Проверка наличия метода
            if (method_exists($objClass, $action)) {
                [$m, $d] = $objClass->$action();
                $this->response($m, $d);
            } else {
                $this->response('Method API not found');
            }
        } else {
            $this->response('API not found');
        }
    }

    private function response($message, $success = 0)
    {
        echo json_encode([
            'result' => $success,
            'data' => $message
        ]);
    }
}
