-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 02, 2024 at 10:55 PM
-- Server version: 10.6.18-MariaDB-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `user_profiles_id` int(10) UNSIGNED NOT NULL,
  `salt` char(64) NOT NULL,
  `user_name` char(255) DEFAULT NULL,
  `email` char(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`users_id`, `user_profiles_id`, `salt`, `user_name`, `email`) VALUES
(1, 1, 'C8lE3m', 'Guest', ''),
(2, 2, 'Y2hU1zY5y', 'Cristian Radu', 'kris_ro@some-non-domain.con'),
(38, 10, 'V3oU2e', 'Cristian Radu', 'kris_ro@some-non-domain.com'),
(42, 3, 'W1aI7o', 'BRETFELEAN M', 'mech.eng@some-non-domain.com'),
(43, 4, 'G9xE0n', 'Alin', 'gabormures@some-non-domain.com'),
(44, 3, 'V3oU2e', 'sorin', 'sorin.crasma@some-non-domain.net'),
(45, 10, 'Y5zL8j', 'Hr', 'infomunca@some-non-domain.com'),
(48, 4, 'V3oU2e', 'Cristian Radu', 'rc@some-non-domain.ro'),
(49, 2, 'R6nB3e', 'DANA', 'danao10@some-non-domain.com'),
(50, 2, 'B8nO0jI8x', 'Octav Goga', 'goga.octav@other-non-domain.com'),
(51, 2, 'A2pQ4qH5i', 'Kristi Rad', 'cristian.radu@some-non-domain.net'),
(52, 2, 'G9wU2dH1k', 'Kristi Radu', 'kristi.radu@some-non-domain.comx'),
(53, 3, 'P9wE7bL8u', 'L Bucur', 'bcrla@other-non-domain.com'),
(54, 2, 'R5sM3xA1l', 'ESCU VIOREL', 'prefabricat@some-non-domain.com'),
(55, 2, 'K6zF4w', 'Michael Moldovan', 'moldovan@some-non-domain.com'),
(56, 2, 'H0yD7a', 'Cazacu Johny', 'fetyte@some-non-domain.com'),
(57, 2, 'U8vV9zX6d', 'Andy Sandu', 'andrea@some-non-domain.com'),
(58, 2, 'K0yE6n', 'Mariasu Maria', 'maria90@some-non-domain.com'),
(59, 2, 'I7tG4gM6r', 'Cintary Lehel', 'nelly.lol@some-non-domain.com'),
(60, 2, 'W3cK7q', 'R Vasilica', 'r_vasy64@other-non-domain.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`users_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_profiles_id` (`user_profiles_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `users_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=300;
COMMIT;
