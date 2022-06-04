<?php

namespace App\Logger;

/*
 * Создадим интерфейс на случай добавления других типов логирования (например, в файл).
 * Сейчас будет только логирование в БД.
 */
interface LoggerInterface
{
    public function log($data);
}
