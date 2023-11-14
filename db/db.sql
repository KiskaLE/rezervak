-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Nov 14, 2023 at 07:35 PM
-- Server version: 8.2.0
-- PHP Version: 8.2.12

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
-- Table structure for table `backup_reservations`
--

CREATE TABLE `backup_reservations` (
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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `breaks`
--

CREATE TABLE `breaks` (
  `id` int NOT NULL,
  `start` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
  `end` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
  `workinghour_id` int NOT NULL,
  `type` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Dumping data for table `breaks`
--

INSERT INTO `breaks` (`id`, `start`, `end`, `workinghour_id`, `type`) VALUES
(24, '15:00', '16:00', 36, 0);

-- --------------------------------------------------------

--
-- Table structure for table `discount_codes`
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

--
-- Dumping data for table `discount_codes`
--

INSERT INTO `discount_codes` (`id`, `user_id`, `code`, `value`, `active`, `type`, `services`) VALUES
(59, 17, 'TEST', 10, 1, 1, '[8]'),
(60, 17, 'TESTG', 24, 1, 0, '[8]');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `reservation_id` int DEFAULT NULL,
  `price` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `reservation_id`, `price`, `status`, `created_at`) VALUES
(112, 169, 600, 0, '2023-11-14 17:04:17'),
(113, 170, 600, 0, '2023-11-14 17:04:32');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
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
  `type` tinyint NOT NULL COMMENT '0 - default 1 - backup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `uuid`, `user_id`, `date`, `service_id`, `start`, `firstname`, `lastname`, `email`, `phone`, `address`, `code`, `city`, `status`, `created_at`, `type`) VALUES
(169, '8d855832-e1ed-4087-bef1-f37330eb07a6', 17, '2023-11-15', 8, '14:00', 'Vojtěch', 'Kylar', 'vojtech.kylar@securitynet.cz', '604141626', '28.rijna', '56151', 'Letohrad', 'VERIFIED', '2023-11-14 17:04:17', 0),
(170, '512ab331-0acc-498f-864e-25cc7563a04c', 17, '2023-11-15', 8, '14:00', 'Vojtěch', 'Kylar', 'vojtech.kylar@securitynet.cz', '604141626', '28.rijna', '56151', 'Letohrad', 'VERIFIED', '2023-11-14 17:04:32', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reservations_delated`
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
  `payment_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `price` int NOT NULL,
  `duration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `user_id`, `name`, `description`, `price`, `duration`) VALUES
(8, 17, 'Poradenství', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Morbi imperdiet, mauris ac auctor dictum, ', 600, 60),
(9, 17, 'Konzultace', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Morbi imperdiet, mauris ac auctor dictum, ', 1000, 60);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
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

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `user_id`, `sample_rate`, `payment_info`, `verification_time`, `number_of_days`, `time_to_pay`) VALUES
(5, 15, 30, NULL, 15, 30, 24),
(6, 16, 60, '', 5, 30, 24),
(7, 17, 30, '', 15, 90, 24),
(8, 19, 30, NULL, 15, 30, 24),
(9, 67, 30, NULL, 15, 30, 24);

-- --------------------------------------------------------

--
-- Table structure for table `users`
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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `username`, `password`, `firstname`, `lastname`, `role`) VALUES
(15, 'ca193dc4-becf-46d1-ab14-1c3e743212aa', 'admin3', '$2y$10$eWlPw6I8FxP/TNtdP/Qfh.eWtZLhqgWCu7vjmbRT/hLVoz02hK0Lm', NULL, NULL, 'ADMIN'),
(16, 'a553e98e-bed5-4604-802f-751dcf8309b0', 'admin', '$2y$10$LcODSb4a6xGZX3bBaVQ.B.2jgbom9eoImzxEGJUUo0l6iPdLcEptK', NULL, NULL, 'ADMIN'),
(17, '9c9d4105-1211-4aa3-8169-52c7938dbcb0', 'vojtakylar@seznam.cz', '$2y$10$0h.waAs9mDJU8NoVjWMlqu6dJageLh92qD7z0Tnkpsu8WME.pKcmC', NULL, NULL, 'ADMIN'),
(19, 'e023bfd7-8d20-4343-a6fc-d2e515830d06', 'admin4', '$2y$10$44E5soOzblq4sEqs6TVJteKC5iMWLs9CsFtyl9remVhe8.5VNoR.y', NULL, NULL, 'ADMIN'),
(67, '42d03d3f-033b-4ecb-83ab-5f2c724ac436', 'admin5', '$2y$10$CTU4m51M10N4LjJTsXUEseQh2QznTEk9kcpkxhtJLU4RBmM4ef9.i', NULL, NULL, 'ADMIN');

-- --------------------------------------------------------

--
-- Table structure for table `workinghours`
--

CREATE TABLE `workinghours` (
  `id` int NOT NULL,
  `weekday` tinyint NOT NULL COMMENT '0- monday 1-tuesday 2-wednesday 3- thursday 4- friday 5-saturday 6- sunday	',
  `start` varchar(255) COLLATE utf8mb3_czech_ci DEFAULT '00:00',
  `stop` varchar(255) COLLATE utf8mb3_czech_ci NOT NULL DEFAULT '00:00',
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

--
-- Dumping data for table `workinghours`
--

INSERT INTO `workinghours` (`id`, `weekday`, `start`, `stop`, `user_id`) VALUES
(22, 0, '00:00', '00:00', 15),
(23, 1, '00:00', '00:00', 15),
(24, 2, '00:00', '00:00', 15),
(25, 3, '00:00', '00:00', 15),
(26, 4, '00:00', '00:00', 15),
(27, 5, '00:00', '00:00', 15),
(28, 6, '00:00', '00:00', 15),
(29, 0, '00:00', '00:00', 16),
(30, 1, '00:00', '00:00', 16),
(31, 2, '00:00', '00:00', 16),
(32, 3, '00:00', '00:00', 16),
(33, 4, '00:00', '00:00', 16),
(34, 5, '00:00', '00:00', 16),
(35, 6, '00:00', '00:00', 16),
(36, 0, '14:00', '18:00', 17),
(37, 1, '14:00', '18:00', 17),
(38, 2, '14:00', '18:00', 17),
(39, 3, '00:00', '00:00', 17),
(40, 4, '00:00', '00:00', 17),
(41, 5, '00:00', '00:00', 17),
(42, 6, '00:00', '00:00', 17),
(43, 0, '00:00', '00:00', 19),
(44, 1, '00:00', '00:00', 19),
(45, 2, '00:00', '00:00', 19),
(46, 3, '00:00', '00:00', 19),
(47, 4, '00:00', '00:00', 19),
(48, 5, '00:00', '00:00', 19),
(49, 6, '00:00', '00:00', 19),
(50, 0, '00:00', '00:00', 67),
(51, 1, '00:00', '00:00', 67),
(52, 2, '00:00', '00:00', 67),
(53, 3, '00:00', '00:00', 67),
(54, 4, '00:00', '00:00', 67),
(55, 5, '00:00', '00:00', 67),
(56, 6, '00:00', '00:00', 67);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_reservations`
--
ALTER TABLE `backup_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`);

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
  ADD UNIQUE KEY `unique_code` (`code`,`user_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`) USING BTREE;

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`);

--
-- Indexes for table `reservations_delated`
--
ALTER TABLE `reservations_delated`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`) USING BTREE,
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
  ADD UNIQUE KEY `weekday` (`weekday`,`user_id`),
  ADD KEY `workinghours_ibfk_1` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_reservations`
--
ALTER TABLE `backup_reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `breaks`
--
ALTER TABLE `breaks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `discount_codes`
--
ALTER TABLE `discount_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `reservations_delated`
--
ALTER TABLE `reservations_delated`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `workinghours`
--
ALTER TABLE `workinghours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backup_reservations`
--
ALTER TABLE `backup_reservations`
  ADD CONSTRAINT `services` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `breaks`
--
ALTER TABLE `breaks`
  ADD CONSTRAINT `breaks_ibfk_1` FOREIGN KEY (`workinghour_id`) REFERENCES `workinghours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD CONSTRAINT `discount_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `workinghours`
--
ALTER TABLE `workinghours`
  ADD CONSTRAINT `workinghours_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
