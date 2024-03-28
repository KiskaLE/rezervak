-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: Mar 28, 2024 at 01:07 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rezervak`
--

-- --------------------------------------------------------

--
-- Table structure for table `breaks`
--

CREATE TABLE `breaks` (
  `id` int NOT NULL,
  `start` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `end` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `workinghour_id` int NOT NULL,
  `type` tinyint NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_codes`
--

CREATE TABLE `discount_codes` (
  `id` int NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `value` int NOT NULL,
  `active` tinyint(1) NOT NULL,
  `type` tinyint NOT NULL COMMENT '0-price\r\n1-%'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `reservation_id` int DEFAULT NULL,
  `id_transaction` int NOT NULL,
  `price` int NOT NULL,
  `discount_code` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `service_id` int NOT NULL,
  `start` datetime NOT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'UNVERIFIED',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint NOT NULL COMMENT '0 - default 1 - backup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations_canceled`
--

CREATE TABLE `reservations_canceled` (
  `id` int NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `service_id` int NOT NULL,
  `start` datetime NOT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'UNVERIFIED',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint NOT NULL COMMENT '0 - default 1 - backup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service2discount_code`
--

CREATE TABLE `service2discount_code` (
  `discount_code_id` int NOT NULL,
  `service_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `price` int NOT NULL,
  `duration` int NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-hidden\r\n0-visible',
  `type` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services_custom_schedules`
--

CREATE TABLE `services_custom_schedules` (
  `id` int NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `start` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `service_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_custom_schedule_days`
--

CREATE TABLE `service_custom_schedule_days` (
  `id` int NOT NULL,
  `uuid` char(36) NOT NULL,
  `service_custom_schedule_id` int NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `type` tinyint NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `backup_info` varchar(500) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `info_email` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `notify_time` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL,
  `sample_rate` int NOT NULL DEFAULT '30',
  `payment_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `verification_time` int NOT NULL DEFAULT '15',
  `number_of_days` int NOT NULL DEFAULT '30',
  `time_to_pay` int NOT NULL DEFAULT '24' COMMENT 'in hours',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `homepage` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `gdpr_url` varchar(500) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT 'https://www.mvcr.cz/gdpr/clanek/co-je-gdpr.aspx',
  `footer` varchar(1000) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `backup_info`, `info_email`, `notify_time`, `user_id`, `sample_rate`, `payment_info`, `verification_time`, `number_of_days`, `time_to_pay`, `email`, `homepage`, `gdpr_url`, `footer`, `created_at`, `updated_at`) VALUES
(1, '<p>Záložní rezervace</p>', 'example@email.com', 0, 7, 30, NULL, 15, 30, 24, 'example@email.com', '', 'https://www.mvcr.cz/gdpr/clanek/co-je-gdpr.aspx', '', '2024-03-28 10:09:05', '2024-03-28 11:03:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `ico` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `dic` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `payment_info` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `uuid` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'example@email.com',
  `username` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `role` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'UNVERIFIED',
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `zip` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ico`, `dic`, `company`, `payment_info`, `phone`, `uuid`, `email`, `username`, `password`, `firstname`, `lastname`, `role`, `address`, `zip`, `city`, `logo_url`, `created_at`, `updated_at`) VALUES
(7, '', '', '', '', '604141626', '8826e844-1289-4b1c-8d5b-833a43292d5e', 'example@email.com', 'admin', '$2y$10$f95OhaYk86rKYCGgkWXkf.qLSl0ySymEagtj/v5XXKtyDoyYMNeIq', 'Vojtech', 'Kylar', 'ADMIN', '', '', '', NULL, '2024-03-28 10:44:43', '2024-03-28 10:44:43');

-- --------------------------------------------------------

--
-- Table structure for table `workinghours`
--

CREATE TABLE `workinghours` (
  `id` int NOT NULL,
  `service_id` int NOT NULL DEFAULT '0',
  `weekday` tinyint NOT NULL COMMENT '0- monday 1-tuesday 2-wednesday 3- thursday 4- friday 5-saturday 6- sunday	',
  `start` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT '00:00' COMMENT 'User timezone',
  `stop` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT '00:00' COMMENT 'User timezone'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workinghours_exceptions`
--

CREATE TABLE `workinghours_exceptions` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `start` datetime NOT NULL COMMENT 'User timezone',
  `end` datetime NOT NULL COMMENT 'User timezone',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `breaks`
--
ALTER TABLE `breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workinghour_id` (`workinghour_id`);

--
-- Indexes for table `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`code`) USING BTREE,
  ADD UNIQUE KEY `uuid` (`uuid`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_transaction` (`id_transaction`),
  ADD KEY `reservation_id` (`reservation_id`) USING BTREE;

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE;

--
-- Indexes for table `reservations_canceled`
--
ALTER TABLE `reservations_canceled`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE;

--
-- Indexes for table `service2discount_code`
--
ALTER TABLE `service2discount_code`
  ADD UNIQUE KEY `discount_code_id_2` (`discount_code_id`,`service_id`),
  ADD KEY `discount_code_id` (`discount_code_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services_custom_schedules`
--
ALTER TABLE `services_custom_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `service_custom_schedule_days`
--
ALTER TABLE `service_custom_schedule_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_custom_schedule_id` (`service_custom_schedule_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `workinghours`
--
ALTER TABLE `workinghours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `workinghours_exceptions`
--
ALTER TABLE `workinghours_exceptions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `breaks`
--
ALTER TABLE `breaks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discount_codes`
--
ALTER TABLE `discount_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations_canceled`
--
ALTER TABLE `reservations_canceled`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services_custom_schedules`
--
ALTER TABLE `services_custom_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_custom_schedule_days`
--
ALTER TABLE `service_custom_schedule_days`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `workinghours`
--
ALTER TABLE `workinghours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workinghours_exceptions`
--
ALTER TABLE `workinghours_exceptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `breaks`
--
ALTER TABLE `breaks`
  ADD CONSTRAINT `breaks_ibfk_1` FOREIGN KEY (`workinghour_id`) REFERENCES `workinghours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `services_custom_schedules`
--
ALTER TABLE `services_custom_schedules`
  ADD CONSTRAINT `services_custom_schedules_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_custom_schedule_days`
--
ALTER TABLE `service_custom_schedule_days`
  ADD CONSTRAINT `service_custom_schedule_days_ibfk_1` FOREIGN KEY (`service_custom_schedule_id`) REFERENCES `services_custom_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
