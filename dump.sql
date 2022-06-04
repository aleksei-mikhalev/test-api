-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 04, 2022 at 09:46 PM
-- Server version: 8.0.17
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `temp`
--

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `name` varchar(5) NOT NULL,
  `enabled` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `name`, `enabled`) VALUES
(1, 'USD', 1),
(2, 'RUB', 1),
(3, 'PLN', 0),
(4, 'EUR', 0);

-- --------------------------------------------------------

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates` (
  `id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `value` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `exchange_rates`
--

INSERT INTO `exchange_rates` (`id`, `currency_id`, `value`) VALUES
(1, 2, '65.00'),
(2, 3, '4.50'),
(3, 4, '0.90');

-- --------------------------------------------------------

--
-- Table structure for table `reasons`
--

DROP TABLE IF EXISTS `reasons`;
CREATE TABLE `reasons` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `reasons`
--

INSERT INTO `reasons` (`id`, `name`) VALUES
(2, 'refund'),
(1, 'stock'),
(3, 'transfer');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_log`
--

DROP TABLE IF EXISTS `transaction_log`;
CREATE TABLE `transaction_log` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `transaction_type` int(1) NOT NULL COMMENT '0 - credit, 1 - debit',
  `amount` decimal(10,2) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `reason` int(11) NOT NULL,
  `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `transaction_log`
--

INSERT INTO `transaction_log` (`id`, `wallet_id`, `transaction_type`, `amount`, `currency_id`, `reason`, `date_update`) VALUES
(1, 1, 0, '1.00', 1, 2, '2022-05-27 22:35:00'),
(2, 1, 0, '1.00', 1, 2, '2022-06-04 13:23:59'),
(3, 1, 1, '1.00', 1, 2, '2022-06-04 13:23:59'),
(4, 1, 1, '1.00', 2, 1, '2022-06-04 13:23:59'),
(5, 1, 1, '1.00', 2, 1, '2022-06-04 13:23:59'),
(6, 1, 1, '1.00', 2, 3, '2022-06-04 13:23:59'),
(7, 1, 1, '1.00', 2, 3, '2022-06-04 13:24:13'),
(8, 1, 1, '1.00', 2, 2, '2022-06-04 13:29:45'),
(9, 1, 1, '1.00', 2, 2, '2022-06-04 13:41:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`) VALUES
(1, 'Alex'),
(2, 'Anton');

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `currency_id`, `balance`) VALUES
(1, 1, 1, '1205.00'),
(5, 1, 2, '65150.00');

--
-- Triggers `wallets`
--
DROP TRIGGER IF EXISTS `check_enabled`;
DELIMITER $$
CREATE TRIGGER `check_enabled` BEFORE INSERT ON `wallets` FOR EACH ROW BEGIN
    DECLARE available INT;

    SELECT COUNT(1) INTO available FROM currencies
     WHERE id = NEW.currency_id
       AND enabled = 1;
    IF available = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Currency not supported';
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`currency_id`);

--
-- Indexes for table `reasons`
--
ALTER TABLE `reasons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `transaction_log`
--
ALTER TABLE `transaction_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trans_wallet_id` (`wallet_id`),
  ADD KEY `fk_trans_currency_id` (`currency_id`),
  ADD KEY `fk_trans_reason` (`reason`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`user_id`,`currency_id`),
  ADD KEY `fk_currency_id` (`currency_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reasons`
--
ALTER TABLE `reasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaction_log`
--
ALTER TABLE `transaction_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD CONSTRAINT `fk_exchange_currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Constraints for table `transaction_log`
--
ALTER TABLE `transaction_log`
  ADD CONSTRAINT `fk_trans_currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_trans_reason` FOREIGN KEY (`reason`) REFERENCES `reasons` (`id`),
  ADD CONSTRAINT `fk_trans_wallet_id` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`);

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `fk_currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
