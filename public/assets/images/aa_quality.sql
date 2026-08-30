-- phpMyAdmin SQL Dump
-- version 4.8.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2018 at 05:12 PM
-- Server version: 10.1.32-MariaDB
-- PHP Version: 5.6.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alternative_account`
--

-- --------------------------------------------------------

--
-- Table structure for table `aa_bank_reason`
--

CREATE TABLE `aa_bank_reason` (
  `quality_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_date` date NOT NULL,
  `status` enum('Active','Inactive','Delete') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `aa_bank_reason`
--

INSERT INTO `aa_bank_reason` (`quality_id`, `name`, `added_date`, `updated_date`, `status`) VALUES
(1, 'R/sand', '2018-06-09 10:36:29', '0000-00-00', 'Active'),
(2, 'Dust\r\n', '2018-06-09 10:36:29', '0000-00-00', 'Active'),
(3, '20MM\r\n', '2018-06-09 10:36:45', '0000-00-00', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aa_bank_reason`
--
ALTER TABLE `aa_bank_reason`
  ADD PRIMARY KEY (`quality_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aa_bank_reason`
--
ALTER TABLE `aa_bank_reason`
  MODIFY `quality_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
