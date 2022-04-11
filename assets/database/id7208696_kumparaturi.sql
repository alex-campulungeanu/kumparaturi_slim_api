-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: 23 Noi 2018 la 07:27
-- Versiune server: 10.1.31-MariaDB
-- PHP Version: 7.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `id7208696_kumparaturi`
--

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

--
-- Salvarea datelor din tabel `shop`
--

INSERT INTO `shop` (`id`, `item`, `status_id`, `created_at`, `user_id`) VALUES
(651, 'paine', 0, '0000-00-00', 1),
(652, 'iaurt', 0, '0000-00-00', 1),
(653, 'servetele nas', 0, '0000-00-00', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `shop`
--
ALTER TABLE `shop`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `shop`
--
ALTER TABLE `shop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=654;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
