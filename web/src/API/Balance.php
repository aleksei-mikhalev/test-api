<?php

namespace App\API;

use App\DB\DBConnect;
use App\Logger\LoggerInterface;
use Exception;

class Balance {

    /**
     * Получение баланса (не будем проверять POST или GET для простоты)
     * @param $method
     * @param $requestUri
     * @param $requestParams
     * @return array
     */
    public function __construct(private string $method,
                                private array $requestUri,
                                private array $requestParams,
                                private LoggerInterface $logger) {}

    /**
     * Получение баланса всех кошельков.
     * Не будем проверять POST или GET для простоты.
     * Пример: http://temp/balance/change/?wallet_id=5&type=debit&amount=50.55&curency=RUB&reason=Test
     */
    public function get(): array
    {
        $userID = array_shift($this->requestUri);

        if (empty($userID)) {
            return ['User ID not correct', 0];
        }

        $stmt = DBConnect::getInstance()->prepare(
            "SELECT c.`name`,
                    w.`balance`
               FROM `wallets` as `w`
                    JOIN `currencies` c ON c.`id` = w.`currency_id`
              WHERE `w`.`user_id` = :user_id"
        );
        $stmt->execute([':user_id' => $userID]);
        $balances = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (count($balances) === 0) {
            return ['User not found', 0];
        }

        return [$balances, 1];
    }

    /**
     * Получение баланса конкретного кошельков.
     * Не будем проверять POST или GET для простоты.
     * Пример: http://temp/balance/change/?wallet_id=5&type=debit&amount=50.55&curency=RUB&reason=Test
     */
    public function get_wallet(): array
    {
        $walletID = array_shift($this->requestUri);

        if (empty($walletID)) {
            return ['Wallet ID not correct', 0];
        }

        $stmt = DBConnect::getInstance()->prepare(
            "SELECT c.`name`,
                    w.`balance`
               FROM `wallets` as `w`
                    JOIN `currencies` c ON c.`id` = w.`currency_id`
              WHERE `w`.`id` = :id"
        );
        $stmt->execute([':id' => $walletID]);
        $balances = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (count($balances) === 0) {
            return ['User not found', 0];
        }

        return [$balances, 1];
    }

    /**
     * Изменение баланса.
     * Только методом POST, но без каких-либо проверок аутентификации для тестового задания).
     * Пример: http://temp/balance/change/?wallet_id=5&type=debit&amount=50.55&curency=RUB&reason=Test
     */
    public function change(): array
    {
/*
        // Закомментируем проверку обязательного метода POST для удобства
        if ($this->method != 'POST') {
            return ['Invalid method: POST is needed', 0];
        }
*/
        // Проверяем минимально необходимые параметры
        $requiredParams = ['wallet_id', 'type', 'amount', 'curency', 'reason'];
        foreach ($requiredParams as $param) {
            if (empty($this->requestParams[$param])) {
                return ['Invalid parameters: ' . $param, 0];
            }
        }

        switch ($this->requestParams['type']) {
            case 'debit':
                $sign = '+';
                $typeInt = 1;
                break;
            case 'credit':
                $sign = '-';
                $typeInt = 0;
                break;
            default:
                return ['Invalid type. Must be "debit" or "credit"', 0];
        }

        /*
         * Получаем ID валюты по ее переданному в API названию.
         * Допустимые значения: USD или RUB (проверка по полю в БД: currencies.enabled = 1).
         */
        $stmt = DBConnect::getInstance()->prepare(
            "SELECT `id`
               FROM `currencies`
              WHERE `name` = :curency
                AND `enabled` = 1"
        );
        $stmt->execute([':curency' => $this->requestParams['curency']]);
        $currencyData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($currencyData)) {
            return ['Curency ' . $this->requestParams['curency']  . ' is not supported', 0];
        }

        // Получаем данные о кошельке
        $stmt = DBConnect::getInstance()->prepare(
            "SELECT `currency_id`,
                    `user_id`
               FROM `wallets`
              WHERE `id` = :wallet_id"
        );
        $stmt->execute([':wallet_id' => $this->requestParams['wallet_id']]);
        $walletData = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Проверяем, нужна ли конвертация валюты
        if ($walletData['currency_id'] !== $currencyData['id']) {
            // Если перевод в долларах меняем валюту на валюту кошелька для вычисления курса конвертации
            $currency = ($this->requestParams['curency'] == 'USD') ? $walletData['currency_id'] : $currencyData['id'];
            $this->convertCurrency($currency, $this->requestParams['curency']);
        }

        // Проверяем причину изменения баланса
        $stmt = DBConnect::getInstance()->prepare(
            "SELECT `id`
               FROM `reasons`
              WHERE `name` = :reason"
        );
        $stmt->execute([':reason' => $this->requestParams['reason']]);
        $reasonData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$reasonData) {
            return ['Reason not correct', 0];
        }

        DBConnect::getInstance()->beginTransaction();
        $sth = DBConnect::getInstance()->prepare(
            "UPDATE `wallets`
                SET `balance` = `balance` {$sign} :amount
              WHERE `id` = :wallet_id"
        );
        $sth->execute([':wallet_id' => $this->requestParams['wallet_id'], ':amount' => $this->requestParams['amount']]);

        // Логируем изменение баланса в БД
        $logResult = $this->logger->log([
            'wallet_id' => $this->requestParams['wallet_id'],
            'transaction_type' => $typeInt,
            'amount' => $this->requestParams['amount'],
            'currency_id' => $currencyData['id'],
            'reason' => $reasonData['id']
        ]);

        // Сохраняем или откатываем транзакцию изменения баланса, если при логировании произошла ошибка
        $logResult ? DBConnect::getInstance()->commit() : DBConnect::getInstance()->rollBack();

        // Выведем для удобства пользователю баланс всех кошельков после изменения для отслеживания изменения баланса
        $this->requestUri = [ $walletData['user_id'] ];

        return $this->get();
    }

    /**
     * Конвертация валюты по текущему курсу.
     * @param int $currency Идентификатор валюты
     */
    public function convertCurrency(int $currency, string $convertFrom): void
    {
        $stmt = DBConnect::getInstance()->prepare(
            "SELECT `value`
               FROM `exchange_rates`
              WHERE `currency_id` = :currency"
        );
        $stmt->execute([':currency' => $currency]);
        $ratio = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($ratio)) {
            throw new Exception('Exchange ratio not found');
        }

        // Если переводим в долларах, то умножаем на курс, иначе делим
        if ($convertFrom == 'USD') {
            $this->requestParams['amount'] *= $ratio['value'];
        } else {
            $this->requestParams['amount'] /= $ratio['value'];
        }
    }
}
