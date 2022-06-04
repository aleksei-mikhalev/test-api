<?php

namespace App\Logger;

use App\DB\DBConnect;

class LoggerDB implements LoggerInterface
{
    public function log($data)
    {
        $sth = DBConnect::getInstance()->prepare(
            "INSERT INTO `transaction_log` (`wallet_id`, `transaction_type`, `amount`, `currency_id`, `reason`)
                    VALUES (:wallet_id, :transaction_type, :amount, :currency_id, :reason)"
        );

        return $sth->execute([
            ':wallet_id' => $data['wallet_id'],
            ':transaction_type' => $data['transaction_type'],
            ':amount' => $data['amount'],
            ':currency_id' => $data['currency_id'],
            ':reason' => $data['reason']
        ]);
    }
}
