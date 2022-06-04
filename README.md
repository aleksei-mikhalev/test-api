# Тестовое задание: API
🚀 PHP 8   🏗 MySQL  📋 PhpMyAdmin  🐋 Docker

___

## Порты

- 3307 - MySQL
- 8081 - PhpMyAdmin
- 8080 - API App

## Установка с Docker

```shell
git clone https://github.com/aleksei-mikhalev/test-api.git api
cd api
docker-compose -p api up --build
```

- API app [http://localhost:8080](http://localhost:8080)
- PhpMyAdmin [http://localhost:8081](http://localhost:8081)

## Установка без Docker

- Склонировать репозиторий: git clone https://github.com/aleksei-mikhalev/test-api.git api
- Поместить все файлы из папки web в корневую директорию проекта/сайта (нужна поддержка .htaccess)
- Импортировать файл dump.sql в нужную БД
- Настроить параметры доступа к БД в файле src\DB\DBConnect.php

## Примеры URL для проверки

Получить баланс кошельков для пользователя с ID = 1:

http://localhost:8080/balance/get/1

Получить баланс конкретного кошелька (ID = 1) пользователя:

http://localhost:8080/balance/get_wallet/1

Увеличить (debit) баланс RUB-кошелька с ID = 5 на сумму 50.55 руб. с указанием причины 'refund':

http://localhost:8080/balance/change/?wallet_id=5&type=debit&amount=50.55&curency=RUB&reason=refund


Автоматическая конвертация валют проходит по курсу из таблицы exchange_rates.

Уменьшить (credit) баланс RUB-кошелька с ID = 5 (RUB-кошелек) на сумму $20.31 с указанием причины 'stock':

http://localhost:8080/balance/change/?wallet_id=5&type=credit&amount=20.31&curency=USD&reason=stock

Увеличить (debit) баланс USD-кошелька с ID = 1 (USD-кошелек) на сумму 65 руб. с указанием причины 'refund':

http://localhost:8080/balance/change/?wallet_id=1&type=debit&amount=65&curency=RUB&reason=stock

## Доп. задание: SQL запрос, который вернет сумму, полученную по причине refund за последние 7 дней
```shell
SELECT SUM(amount) as `sum`
  FROM `transaction_log` as `tl`
  JOIN `reasons` as `r` ON `r`.`id` = `tl`.`reason`
 WHERE `r`.`name` = 'refund'
   AND `tl`.`date_update` >= DATE(NOW() - INTERVAL 7 DAY)
```

## Описание некоторых решений

Дамп базы данных находится в файле dump.sql
Импортируется автоматически при установке через docker-compose.

Основные проверки со стороны БД сделаны с помощью индексов (уникальные, внешние ключи).
Например, так проверяется возможность иметь только по одному типу кошелов (USD и RUB) для пользователя.

Разрешеные валюты помечаются полем enabled в таблице currencies.

Для проверки возможности создавать в таблице только разрешенные (currencies.enabled) кошельки
используется триггер check_enabled для таблицы wallets.

Закомментировал проверку метода POST при изменении баланса для удобства тетсирования методом GET прямо в браузере.
Если расскомментировать проверку в файле web\src\API\Balance.php (строки 90-95),
то будут разрешены только POST-запросы с соответсвующими параметрами (названия совпадают с GET-параметрами).

## Исходный текст задачи

Требования
Реализовать методы API для работы с кошельком пользователя. Ограничения:
- У пользователя может быть только один кошелек.
- Поддерживаемые валюты: USD и RUB.
- При вызове метода для изменения кошелька на сумму с отличной валютой от валюты кошелька, сумма должна конвертироваться по курсу.
- Курсы обновляются периодически.
- Все изменения кошелька должны фиксироваться в БД.

Метод для изменения баланса
Обязательные параметры метода:
- ID кошелька (например: 241, 242)
- Тип транзакции (debit или credit)
- Сумма, на которую нужно изменить баланс
- Валюта суммы (допустимы значения: USD, RUB)
- Причина изменения счета (например: stock, refund). Список причин фиксирован.

Метод для получения текущего баланса
Обязательные параметры метода:
- ID кошелька (например: 241, 242)

SQL запрос
Написать SQL запрос, который вернет сумму, полученную по причине refund за последние 7 дней.

Технические требования
- Серверная логика должна быть написана на PHP версии >=7.0
- Для хранения данных должна использоваться реляционная СУБД
- Должны быть инструкции для развертывания проекта

Допущения
- Выбор дополнительных технологий не ограничен;
- Все спорные вопросы в задаче может и должен решать Исполнитель;
