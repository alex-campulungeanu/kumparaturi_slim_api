-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: 27 Mar 2019 la 08:13
-- Versiune server: 5.7.24
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nipuro1_kumparaturi`
--

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `setting_type`
--

CREATE TABLE `setting_type` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Salvarea datelor din tabel `setting_type`
--

INSERT INTO `setting_type` (`id`, `name`) VALUES
(1, 'send notification'),
(2, 'receive notification');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `shop`
--

CREATE TABLE `shop` (
  `id` int(11) NOT NULL,
  `item` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `status_id` int(11) NOT NULL,
  `created_at` date NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `REMEMBER_TOKEN` varchar(100) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_image_path` varchar(400) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `users_token`
--

CREATE TABLE `users_token` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `create_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `user_setting_mn`
--

CREATE TABLE `user_setting_mn` (
  `id` int(11) NOT NULL,
  `setting_type_id` int(11) DEFAULT NULL,
  `setting_value_number` int(11) DEFAULT NULL,
  `setting_value_string` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `setting_type`
--
ALTER TABLE `setting_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shop`
--
ALTER TABLE `shop`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_IDX` (`email`),
  ADD UNIQUE KEY `users_username_IDX` (`username`);

--
-- Indexes for table `users_token`
--
ALTER TABLE `users_token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_token_token_IDX` (`token`);

--
-- Indexes for table `user_setting_mn`
--
ALTER TABLE `user_setting_mn`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `setting_type`
--
ALTER TABLE `setting_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shop`
--
ALTER TABLE `shop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users_token`
--
ALTER TABLE `users_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

--
-- AUTO_INCREMENT for table `user_setting_mn`
--
ALTER TABLE `user_setting_mn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
