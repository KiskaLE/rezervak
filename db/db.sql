-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Počítač: db
-- Vytvořeno: Čtv 16. lis 2023, 14:27
-- Verze serveru: 8.2.0
-- Verze PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `rezervak`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `breaks`
--

CREATE TABLE `breaks` (
  `id` int NOT NULL,
  `start` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
  `end` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
  `workinghour_id` int NOT NULL,
  `type` tinyint NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `discount_codes`
--

CREATE TABLE `discount_codes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_czech_ci NOT NULL,
  `value` int NOT NULL,
  `active` tinyint(1) NOT NULL,
  `type` tinyint NOT NULL COMMENT '0-price\r\n1-%',
  `services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `reservation_id` int DEFAULT NULL,
  `price` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `reservations`
--

CREATE TABLE `reservations` (
  `id` int NOT NULL,
  `uuid` char(36) COLLATE utf8mb3_czech_ci NOT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `service_id` int NOT NULL,
  `start` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'UNVERIFIED',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint NOT NULL COMMENT '0 - default 1 - backup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `reservations_delated`
--

CREATE TABLE `reservations_delated` (
  `id` int NOT NULL,
  `uuid` char(36) COLLATE utf8mb3_czech_ci NOT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `service_id` int NOT NULL,
  `start` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'UNVERIFIED',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint NOT NULL COMMENT '0 - default 1 - backup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `price` int NOT NULL,
  `duration` int NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-hidden\r\n0-visible',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `sample_rate` int NOT NULL DEFAULT '30',
  `payment_info` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `verification_time` int NOT NULL DEFAULT '15',
  `number_of_days` int NOT NULL DEFAULT '30',
  `time_to_pay` int NOT NULL DEFAULT '24' COMMENT 'in hours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `uuid` char(36) COLLATE utf8mb3_czech_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'UNVERIFIED'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `workinghours`
--

CREATE TABLE `workinghours` (
  `id` int NOT NULL,
  `weekday` tinyint NOT NULL COMMENT '0- monday 1-tuesday 2-wednesday 3- thursday 4- friday 5-saturday 6- sunday	',
  `start` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT '00:00',
  `stop` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL DEFAULT '00:00',
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

--
-- Indexy pro exportované tabulky
--

--
-- Indexy pro tabulku `breaks`
--
ALTER TABLE `breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workinghour_id` (`workinghour_id`);

--
-- Indexy pro tabulku `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`code`,`user_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`) USING BTREE;

--
-- Indexy pro tabulku `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`);

--
-- Indexy pro tabulku `reservations_delated`
--
ALTER TABLE `reservations_delated`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`);

--
-- Indexy pro tabulku `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexy pro tabulku `workinghours`
--
ALTER TABLE `workinghours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `weekday` (`weekday`,`user_id`),
  ADD KEY `workinghours_ibfk_1` (`user_id`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `breaks`
--
ALTER TABLE `breaks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `discount_codes`
--
ALTER TABLE `discount_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `reservations_delated`
--
ALTER TABLE `reservations_delated`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `workinghours`
--
ALTER TABLE `workinghours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `breaks`
--
ALTER TABLE `breaks`
  ADD CONSTRAINT `breaks_ibfk_1` FOREIGN KEY (`workinghour_id`) REFERENCES `workinghours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD CONSTRAINT `discount_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `workinghours`
--
ALTER TABLE `workinghours`
  ADD CONSTRAINT `workinghours_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
