-- phpMyAdmin SQL Dump
-- version 4.0.10.18
-- https://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Jun 21, 2018 at 07:58 AM
-- Server version: 5.6.39-cll-lve
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `yuumo`
--

-- --------------------------------------------------------

--
-- Table structure for table `fs_area`
--

CREATE TABLE IF NOT EXISTS `fs_area` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `fs_area`
--

INSERT INTO `fs_area` (`id`, `country_id`, `state_id`, `city_id`, `name`, `status`, `created_date`, `updated_date`) VALUES
(1, 2, 1, 1, 'E', 'active', '2017-10-26 14:55:36', '2017-11-01 12:20:52'),
(2, 1, 5, 6, 'b block', 'inactive', '2017-12-04 11:12:16', '2017-12-04 05:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `fs_city`
--

CREATE TABLE IF NOT EXISTS `fs_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=31 ;

--
-- Dumping data for table `fs_city`
--

INSERT INTO `fs_city` (`id`, `country_id`, `state_id`, `name`, `status`, `created_date`, `updated_date`) VALUES
(1, 2, 1, 'los angles', 'active', '2017-10-26 14:49:58', '2017-11-01 11:29:54'),
(2, 1, 2, 'noida', 'active', '2017-10-26 14:50:20', '2017-10-26 09:20:20'),
(3, 1, 2, 'Hardoi', 'active', '2017-10-26 14:50:36', '2017-10-26 13:12:09'),
(4, 3, 4, 'sid', 'active', '2017-10-26 14:50:48', '2017-10-26 09:20:48'),
(5, 2, 1, 'df', 'delete', '2017-10-26 14:52:21', '2017-12-11 10:35:39'),
(6, 1, 17, 'New Ashok Nagar', 'active', '2017-10-26 18:43:50', '2017-12-21 05:09:18'),
(7, 2, 1, 'dd', 'delete', '2017-11-03 13:08:46', '2017-12-11 10:35:39'),
(8, 1, 2, 'jalaun', 'inactive', '2017-12-04 11:09:52', '2017-12-04 05:39:52'),
(9, 16, 14, 'lklklklk', 'delete', '2017-12-11 09:59:43', '2017-12-21 05:10:59'),
(10, 23, 16, 'ioio', 'delete', '2017-12-11 10:14:37', '2017-12-11 10:35:39'),
(11, 1, 17, 'New Ashok', 'delete', '2017-12-11 14:16:17', '2017-12-11 10:35:39'),
(12, 1, 5, 'sdsd', 'delete', '2017-12-13 17:42:07', '2017-12-13 12:12:16'),
(13, 1, 5, 'yt', 'active', '2017-12-14 15:47:11', '2017-12-21 05:02:15'),
(14, 1, 8, 'g', 'active', '2017-12-21 10:27:43', '2017-12-21 04:57:43'),
(15, 1, 21, 'h', 'active', '2017-12-21 10:27:53', '2017-12-21 04:57:53'),
(16, 1, 21, 'bvbvbvb', 'active', '2017-12-21 10:28:30', '2017-12-21 04:58:30'),
(17, 1, 5, 'gfdgfdgfgfg', 'active', '2017-12-21 10:28:36', '2017-12-21 05:10:04'),
(18, 1, 8, 'bvcbvcbcvbcvbbv', 'active', '2017-12-21 10:28:43', '2017-12-21 04:58:43'),
(19, 1, 5, 'bcbcvbvcbcvbcvbbbbbbbb', 'active', '2017-12-21 10:28:52', '2017-12-21 04:58:52'),
(20, 8, 2, 'bbbbb', 'active', '2017-12-21 10:29:05', '2017-12-21 04:59:05'),
(21, 8, 2, 'hhhhhhhhhhhhhhhhhhhhhhhhhhhh', 'active', '2017-12-21 10:29:11', '2017-12-21 05:09:48'),
(22, 8, 2, 'qqqq', 'active', '2017-12-21 10:29:17', '2017-12-21 04:59:17'),
(23, 8, 2, 'Bbbb', 'active', '2017-12-21 10:29:28', '2017-12-21 05:08:37'),
(24, 8, 2, 'ggggggggggggggg', 'active', '2017-12-21 10:29:36', '2017-12-21 04:59:36'),
(25, 8, 2, 'ggggggggggggggggggggg', 'active', '2017-12-21 10:29:43', '2017-12-21 04:59:43'),
(26, 8, 2, 'ggggggggggggggggggggggg', 'active', '2017-12-21 10:29:51', '2017-12-21 04:59:51'),
(27, 8, 2, 'bbbbbbbbbbbbbbb', 'active', '2017-12-21 10:29:59', '2017-12-21 04:59:59'),
(28, 8, 2, 'nnn', 'active', '2017-12-21 10:30:07', '2017-12-21 05:00:07'),
(29, 8, 2, 'fffff', 'active', '2017-12-21 10:30:17', '2017-12-21 05:00:17'),
(30, 8, 2, 'kj', 'active', '2017-12-21 10:30:35', '2017-12-21 05:06:50');

-- --------------------------------------------------------

--
-- Table structure for table `fs_cms`
--

CREATE TABLE IF NOT EXISTS `fs_cms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `fs_cms`
--

INSERT INTO `fs_cms` (`id`, `name`, `content`, `status`, `created_date`, `updated_date`) VALUES
(1, 'about us', '<ul>\r\n	<li>Dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages.</li>\r\n</ul>', 'active', '0000-00-00 00:00:00', '2017-12-19 05:22:38'),
(2, 'Terms and Conditions', '<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages tset</p>', 'active', '0000-00-00 00:00:00', '2017-12-06 10:48:56'),
(3, 'Privacy Policy', '<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages</p>', 'active', '0000-00-00 00:00:00', '2017-12-06 10:49:03'),
(4, 'Press & News', '<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages</p>', 'active', '0000-00-00 00:00:00', '2017-12-20 12:05:14'),
(5, 'Customer Service', '<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages</p>', 'active', '0000-00-00 00:00:00', '2017-12-20 12:05:33'),
(6, 'Guidelines For Users', '<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages</p>', 'active', '0000-00-00 00:00:00', '2017-12-20 12:05:57'),
(7, 'Return Policy', '<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages</p>', 'active', '0000-00-00 00:00:00', '2017-12-20 12:06:08');

-- --------------------------------------------------------

--
-- Table structure for table `fs_contactus`
--

CREATE TABLE IF NOT EXISTS `fs_contactus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(150) NOT NULL,
  `last_name` varchar(150) NOT NULL,
  `mobile` bigint(19) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `fs_country`
--

CREATE TABLE IF NOT EXISTS `fs_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `fs_country`
--

INSERT INTO `fs_country` (`id`, `name`, `currency`, `status`, `created_date`, `updated_date`) VALUES
(1, 'India', 'INR', 'active', '2017-10-24 18:24:01', '2017-12-14 10:52:20'),
(2, 'USA', 'Dollar', 'active', '2017-10-25 10:06:58', '2018-03-17 17:50:04');

-- --------------------------------------------------------

--
-- Table structure for table `fs_cuisine_category`
--

CREATE TABLE IF NOT EXISTS `fs_cuisine_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `fs_cuisine_category`
--

INSERT INTO `fs_cuisine_category` (`id`, `name`, `status`, `created_date`, `updated_date`) VALUES
(1, 'Greek', 'active', '2017-10-31 06:06:20', '2017-10-31 05:06:20'),
(2, 'American', 'active', '2017-10-31 06:06:55', '2017-11-01 12:02:29'),
(3, 'British', 'active', '2017-10-31 06:07:02', '2017-10-31 05:07:02'),
(4, 'Caribbean', 'active', '2017-10-31 06:07:10', '2017-10-31 05:07:10'),
(5, 'Chinese', 'active', '2017-10-31 06:07:17', '2017-10-31 05:07:17'),
(6, 'French', 'active', '2017-10-31 06:07:26', '2017-10-31 05:07:26'),
(7, 'Indian', 'active', '2017-10-31 06:07:42', '2017-10-31 05:07:42'),
(8, 'Italian', 'active', '2017-10-31 06:07:48', '2017-10-31 05:07:48'),
(9, 'Japanese', 'active', '2017-10-31 06:08:23', '2017-10-31 05:08:23'),
(10, 'Mediterranean', 'active', '2017-10-31 06:08:52', '2017-10-31 05:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `fs_menu`
--

CREATE TABLE IF NOT EXISTS `fs_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'who have this menu',
  `cuisine_category_id` int(11) NOT NULL,
  `menu_category_id` int(11) NOT NULL,
  `menu_sub_category_id` int(11) NOT NULL,
  `menu_type` enum('1','2','3') NOT NULL COMMENT '1=>food,2=>drink,3=>desserts',
  `food_type` enum('1','2') NOT NULL COMMENT '1=>Veg, 2=> non veg',
  `menu_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `item_availability` enum('1','2','3','4','5','6') NOT NULL DEFAULT '5' COMMENT '1=>breakfast,2=>dinner,3=>lunch,4=>evening,5=>full day,6=>full night',
  `status` enum('active','inactive','delete') NOT NULL,
  `is_spicy` enum('1','2') NOT NULL COMMENT '1=>yes,2=>no',
  `tax_amount` float(4,2) NOT NULL,
  `from_time` time NOT NULL,
  `to_time` time NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=23 ;

--
-- Dumping data for table `fs_menu`
--

INSERT INTO `fs_menu` (`id`, `user_id`, `cuisine_category_id`, `menu_category_id`, `menu_sub_category_id`, `menu_type`, `food_type`, `menu_name`, `description`, `image`, `item_availability`, `status`, `is_spicy`, `tax_amount`, `from_time`, `to_time`, `created_date`, `updated_date`) VALUES
(1, 19, 5, 1, 3, '1', '1', 'Test', '', '805b6e03569f5e4b417623a12cec9bfe.png', '3', 'delete', '1', 1.00, '02:00:00', '19:00:00', '2017-11-29 16:41:36', '2017-12-07 06:58:09'),
(2, 19, 7, 1, 4, '1', '1', 'Dii', '', '7b5b3a9317fc187019f22ad386d38293.png', '2', 'delete', '1', 1.00, '02:00:00', '10:00:00', '2017-12-04 15:39:25', '2017-12-07 06:58:16'),
(3, 114, 5, 1, 4, '1', '1', 'combo', '', 'b972e1694a1de241b1a6c2f63e53431c.jpg', '4', 'active', '1', 1.00, '02:00:00', '14:00:00', '2017-12-07 12:31:08', '2017-12-07 07:01:08'),
(4, 138, 7, 1, 4, '1', '1', 'hrrr', '', '45a0fb4ca2cdf12255f1159318601655.jpg', '1', 'active', '1', 1.00, '10:00:00', '11:00:00', '2017-12-14 14:14:32', '2017-12-14 10:43:40'),
(5, 138, 7, 12, 13, '1', '1', 'testing phase', '', '78f5e037a8b33a38efcdd4a0eeb576d7.jpg', '5', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-14 14:27:18', '2017-12-14 08:57:18'),
(6, 138, 1, 1, 3, '1', '1', 'sdfgsdfg', '', 'e63e98db29c80001eb9714f476191caa.jpg', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-14 15:18:56', '2017-12-14 09:48:56'),
(7, 141, 5, 1, 4, '1', '1', 'jj', '', '77c405dc3a1be53b839b3998d0c418f8.png', '1', 'inactive', '1', 17.23, '15:00:00', '19:00:00', '2017-12-14 18:12:46', '2017-12-14 12:42:58'),
(8, 141, 9, 2, 14, '2', '2', 'gh', '', 'c5a48ce3802a28407d7040b26f6b50f0.png', '3', 'inactive', '2', 20.00, '18:00:00', '18:00:00', '2017-12-14 18:13:49', '2017-12-14 12:43:49'),
(9, 6, 1, 1, 3, '1', '2', 'menu234545556', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-14 18:31:02', '2017-12-14 13:02:56'),
(10, 130, 7, 1, 3, '1', '1', 'yrtyrty', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 11:13:33', '2017-12-15 05:43:33'),
(11, 130, 7, 1, 3, '1', '1', 'fgsdfsd', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 11:14:35', '2017-12-15 05:44:35'),
(12, 130, 1, 1, 3, '1', '1', 'dsadsad', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 11:16:37', '2017-12-15 05:46:37'),
(13, 130, 7, 5, 15, '1', '1', 'rtertert', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 12:51:52', '2017-12-15 07:21:52'),
(14, 130, 2, 2, 14, '1', '1', 'sadas', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 12:53:20', '2017-12-15 07:23:20'),
(15, 130, 2, 5, 15, '1', '1', 'eqweqweqe', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 12:53:47', '2017-12-15 07:23:47'),
(16, 130, 7, 5, 15, '1', '1', 'rrrrr', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-15 12:55:39', '2017-12-15 07:25:39'),
(17, 19, 5, 1, 3, '3', '1', 'Menu Name *', '', '', '4', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-18 10:57:40', '2017-12-18 05:27:40'),
(18, 19, 3, 1, 4, '1', '1', 'Menu123446', '', '', '4', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-18 10:58:15', '2017-12-18 05:28:15'),
(19, 19, 5, 1, 4, '1', '1', 'menu hai yahan', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2017-12-18 18:15:39', '2017-12-18 12:46:54'),
(20, 4, 1, 1, 3, '1', '1', 'chenise', '', 'b283e3c1e0028b52ae4c9c1fe78d0048.png', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2018-03-18 05:45:19', '2018-03-18 04:45:19'),
(21, 4, 2, 1, 4, '1', '1', 'Italy', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2018-03-23 19:12:12', '2018-03-23 18:12:12'),
(22, 4, 2, 1, 4, '1', '1', 'chowmeen', '', '', '1', 'active', '1', 1.00, '00:00:00', '00:00:00', '2018-03-26 19:19:30', '2018-03-26 17:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `fs_menu_category`
--

CREATE TABLE IF NOT EXISTS `fs_menu_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;

--
-- Dumping data for table `fs_menu_category`
--

INSERT INTO `fs_menu_category` (`id`, `parent`, `name`, `status`, `created_date`, `updated_date`) VALUES
(1, 0, 'Chinese', 'active', '2017-11-29 14:38:46', '2017-12-05 04:39:16'),
(2, 0, 'South Indian', 'active', '2017-11-29 14:39:16', '2017-12-04 06:57:54'),
(3, 1, 'dimsum', 'active', '2017-11-29 14:51:46', '2017-12-01 12:25:18'),
(4, 1, 'noodles', 'active', '2017-12-01 17:55:42', '2017-12-01 12:25:42'),
(5, 0, 'Indian', 'active', '2017-12-11 11:41:12', '2017-12-11 08:26:43'),
(6, 1, 'rasgulla', 'delete', '2017-12-11 11:43:01', '2018-03-18 04:09:11'),
(13, 2, 'chinese 1', 'active', '2017-12-11 14:19:27', '2018-03-18 04:12:58'),
(14, 2, 'chinese 2', 'active', '2017-12-11 14:26:14', '2018-03-18 04:13:03'),
(15, 2, 'chinese 3', 'active', '2017-12-14 16:22:41', '2018-03-18 04:13:07'),
(16, 0, 'Punjabi', 'active', '2017-12-19 11:28:32', '2017-12-21 05:42:08'),
(17, 5, 'Chole', 'active', '2017-12-19 11:29:47', '2018-03-18 04:12:38'),
(18, 5, 'hh', 'active', '2017-12-21 11:14:25', '2018-03-18 04:12:40'),
(19, 5, 'ghhh', 'delete', '2017-12-21 11:17:06', '2017-12-21 07:00:13');

-- --------------------------------------------------------

--
-- Table structure for table `fs_menu_varient`
--

CREATE TABLE IF NOT EXISTS `fs_menu_varient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `menu_varient_name` varchar(255) NOT NULL,
  `unit_value` varchar(255) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `price` float NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `serving` int(11) NOT NULL,
  `yield` int(11) NOT NULL,
  `position` int(11) NOT NULL COMMENT 'this is sort order for all',
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

--
-- Dumping data for table `fs_menu_varient`
--

INSERT INTO `fs_menu_varient` (`id`, `menu_id`, `menu_varient_name`, `unit_value`, `unit_id`, `price`, `status`, `serving`, `yield`, `position`, `created_date`, `updated_date`) VALUES
(1, 1, 'tt', '1', 1, 1, 'active', 1, 1, 0, '2017-11-29 16:41:36', '2017-11-29 11:11:36'),
(2, 2, 'DEdd', '2', 1, 21, 'active', 2, 2, 0, '2017-12-04 15:39:25', '2017-12-04 10:09:25'),
(3, 3, 'test', '2', 1, 500, 'active', 200, 300, 0, '2017-12-07 12:31:08', '2017-12-07 07:01:08'),
(4, 4, 'fgfg', '5', 6, 200, 'active', 10, 50, 0, '2017-12-14 14:14:32', '2017-12-14 08:44:32'),
(5, 5, 'sdf', '5', 4, 32432, 'active', 34324, 43243, 0, '2017-12-14 14:27:18', '2017-12-14 08:57:18'),
(6, 6, 'sdfgs', '12432', 1, 12345, 'active', 21345, 12345, 0, '2017-12-14 15:18:56', '2017-12-14 09:48:56'),
(7, 7, 'k', '8', 1, 7, 'inactive', 6, 66, 0, '2017-12-14 18:12:46', '2017-12-14 12:42:46'),
(8, 8, 'fd', '6', 1, 6, 'inactive', 6, 6, 0, '2017-12-14 18:13:49', '2017-12-14 12:43:49'),
(9, 9, 'gghg', '22', 2, 22, 'active', 22, 22, 0, '2017-12-14 18:31:02', '2017-12-14 13:01:02'),
(10, 10, 'hfgfh', '756', 3, 67567, 'active', 56756, 65756, 0, '2017-12-15 11:13:33', '2017-12-15 05:43:33'),
(11, 11, 'fgdfgd', 'd', 3, 6, 'active', 6, 6, 0, '2017-12-15 11:14:35', '2017-12-15 05:44:35'),
(12, 12, 'asdasdas', 'c', 3, 0, 'active', 6, 0, 0, '2017-12-15 11:16:37', '2017-12-15 05:46:37'),
(13, 13, 'ertert', '55', 6, 55, 'active', 858, 8, 0, '2017-12-15 12:51:52', '2017-12-15 07:21:52'),
(14, 14, 'asdaa', '43', 4, 3, 'active', 3, 3, 0, '2017-12-15 12:53:20', '2017-12-15 07:23:20'),
(15, 15, 'qweq', '43243', 3, 34234, 'active', 2324, 23423, 0, '2017-12-15 12:53:47', '2017-12-15 07:23:47'),
(16, 16, 'sdfdsf', '343', 6, 43535, 'active', 54353, 43534, 0, '2017-12-15 12:55:39', '2017-12-15 07:25:39'),
(17, 17, 'Menu Name', '23', 2, 23, 'active', 23, 3, 0, '2017-12-18 10:57:40', '2017-12-18 05:27:40'),
(18, 18, '23', '45', 1, 67, 'active', 6, 67, 0, '2017-12-18 10:58:16', '2017-12-18 05:28:16'),
(19, 19, 'vgn', '45', 2, 45, 'active', 45, 45, 0, '2017-12-18 18:15:39', '2017-12-18 12:45:39'),
(20, 20, 'asdf', '1', 1, 123, 'active', 1, 1, 0, '2018-03-18 05:45:19', '2018-03-18 04:45:19'),
(21, 20, 'asdf', '1', 1, 123, 'active', 1, 1, 0, '2018-03-23 19:11:41', '2018-03-23 18:11:41'),
(22, 21, 'italy1', '12', 1, 12, 'active', 2, 121, 0, '2018-03-23 19:12:12', '2018-03-23 18:12:12'),
(23, 22, 'chowmeen half plate', '1', 3, 20, 'active', 1, 1, 0, '2018-03-26 19:19:30', '2018-03-26 17:19:30'),
(24, 22, 'chowmeen full plate', '2', 3, 30, 'active', 2, 1, 0, '2018-03-26 19:19:30', '2018-03-26 17:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `fs_method`
--

CREATE TABLE IF NOT EXISTS `fs_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `method_value` varchar(100) NOT NULL,
  `created_date` datetime NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

--
-- Dumping data for table `fs_method`
--

INSERT INTO `fs_method` (`id`, `module_id`, `method_name`, `method_value`, `created_date`, `status`) VALUES
(1, 0, 'Add', 'add', '2016-10-04 14:53:09', 'active'),
(2, 0, 'Edit', 'edit', '2016-10-04 14:53:09', 'active'),
(3, 0, 'List', 'index', '2016-10-04 14:53:45', 'active'),
(4, 0, 'Delete', 'delete', '2016-10-04 14:53:45', 'active'),
(5, 0, 'Export', 'export', '2016-10-04 14:53:45', 'active'),
(6, 0, 'Payment List', 'payment_list', '2016-10-04 14:53:45', 'active'),
(7, 0, 'Edit', 'userinfo_edit', '2016-10-04 14:53:45', 'active'),
(8, 0, 'Approve', 'changeapprove', '2016-10-04 14:53:45', 'active'),
(9, 0, 'Reject', 'reject_trans', '2016-10-04 14:53:45', 'active'),
(10, 0, 'View', 'view', '2016-10-04 14:53:45', 'active'),
(11, 0, 'Approved/Disapproved', 'approved_disapproved', '2016-10-04 14:53:45', 'active'),
(12, 0, 'Active/Inactive', 'changeRecipeStatus', '2016-10-04 14:53:45', 'active'),
(13, 0, 'Approved/Disapproved', 'changeapprove', '2016-10-04 14:53:45', 'active'),
(14, 0, 'Active/Inactive', 'changeBlogStatus', '2016-10-04 14:53:45', 'active'),
(15, 0, 'Gallery', 'gallery', '2016-10-04 14:53:45', 'active'),
(16, 0, 'Social Media', 'social_media', '2016-10-04 14:53:45', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `fs_modules`
--

CREATE TABLE IF NOT EXISTS `fs_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `module_value` varchar(100) NOT NULL,
  `related_method` varchar(255) NOT NULL,
  `seq_order` varchar(10) NOT NULL,
  `for_seller` enum('0','1') NOT NULL COMMENT '0->no,1->yes',
  `created_at` datetime NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=55 ;

--
-- Dumping data for table `fs_modules`
--

INSERT INTO `fs_modules` (`id`, `parent_id`, `module_name`, `module_value`, `related_method`, `seq_order`, `for_seller`, `created_at`, `status`) VALUES
(1, 0, 'Master Setup', 'mastersetup', '', '', '0', '2016-10-04 11:00:50', 'active'),
(2, 0, 'Manage Buyer', 'managebuyer', '', '', '0', '2016-10-04 11:00:50', 'active'),
(3, 0, 'Manage Food Joint', 'managefoodjoint', '', '', '0', '2016-10-04 11:00:50', 'active'),
(4, 0, 'Manage Order', 'manageorder', '', '', '0', '2016-10-04 11:00:50', 'active'),
(5, 0, 'Manage Recipes', 'managerecipes', '', '', '0', '2016-10-04 11:00:50', 'active'),
(6, 0, 'Manage Blogs', 'manageblogs', '', '', '0', '2016-10-04 11:00:50', 'active'),
(7, 0, 'Manage Ratings reviews', 'manageratingreviews', '', '', '0', '2016-10-04 11:00:50', 'active'),
(8, 0, 'Manage Events', 'manageevents', '', '', '0', '2016-10-04 11:00:50', 'active'),
(9, 0, 'Manage Cooking Institutes', 'managecookinginstitutes', '', '', '0', '2016-10-04 11:00:50', 'active'),
(10, 0, 'Manage Become Chefs', 'managebecomechefs', '', '', '0', '2016-10-04 11:00:50', 'active'),
(11, 0, 'Manage Setting', 'managesetting', '', '', '0', '2016-10-04 11:00:50', 'active'),
(12, 0, 'Manage Roles', 'manageroles', '', '', '0', '2016-10-04 11:00:50', 'active'),
(13, 1, 'Country', 'country', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(14, 1, 'State', 'state', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(15, 1, 'City', 'city', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(16, 1, 'Cuisine Category', 'cuisine_category', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(17, 1, 'Unit', 'unit', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(18, 1, 'Coupon', 'coupon', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(19, 1, 'Menu Category', 'menu_category', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(20, 1, 'Menu Sub Category', 'menu_sub_category', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(21, 1, 'Cms', 'cms', '2,3,5', '', '0', '2016-10-04 11:00:50', 'active'),
(22, 1, 'Plan', 'plan', '2,3', '', '0', '2016-10-04 11:00:50', 'active'),
(23, 2, 'Buyer', 'buyer', '2,3,5', '', '0', '2016-10-04 11:00:50', 'active'),
(24, 3, 'Food Joint', 'seller', '1,7,3,4,5,6', '', '0', '2016-10-04 11:00:50', 'active'),
(25, 3, 'Food Joint Approval', 'food_joint_approval', '3,8,9', '', '0', '2016-10-04 11:00:50', 'active'),
(26, 4, 'Orders', 'orders', '3,5,10', '', '0', '2016-10-04 11:00:50', 'active'),
(27, 5, 'Recipe', 'recipe', '1,2,3,5,10,11,12', '', '0', '2016-10-04 11:00:50', 'active'),
(28, 6, 'Blogs', 'blogs', '3,5,13,14', '', '0', '2016-10-04 11:00:50', 'active'),
(29, 8, 'Events', 'events', '1,2,3,4,5,10', '', '0', '2016-10-04 11:00:50', 'active'),
(30, 9, 'Cooking Institute', 'cooking_institutes', '1,2,3,4,5,10,15', '', '0', '2016-10-04 11:00:50', 'active'),
(31, 10, 'Become Chef', 'become_chef', '3,5,13', '', '0', '2016-10-04 11:00:50', 'active'),
(32, 11, 'Settings', 'settings', '16', '', '0', '2016-10-04 11:00:50', 'active'),
(34, 12, 'Roles', 'role', '1,2,3', '', '0', '2016-10-04 11:00:50', 'active'),
(35, 12, 'Users', 'user', '1,2,3', '', '0', '2016-10-04 11:00:50', 'active'),
(36, 7, 'Blogs Rating Review', 'blogs_rating_review', '3,5,13', '', '0', '2016-10-04 11:00:50', 'active'),
(37, 7, 'Recipes Rating Review', 'recipes_rating_review', '3,5,13', '', '0', '2016-10-04 11:00:50', 'active'),
(38, 7, 'Stall Rating Review', 'stalls_rating_review', '3,5,13', '', '0', '2016-10-04 11:00:50', 'active'),
(39, 0, 'Seller Blogs', 'seller_blogs', '', '', '1', '2016-10-04 11:00:50', 'active'),
(40, 39, 'Seller Blogs', 'seller_blogs', '1,2,3,4,5,10', '', '0', '2016-10-04 11:00:50', 'active'),
(41, 0, 'Featured Plans', 'featured_plans', '', '', '1', '2016-10-04 11:00:50', 'active'),
(42, 41, 'Featured Plans', 'featured_plans', '3', '', '0', '2016-10-04 11:00:50', 'active'),
(43, 0, 'Award', 'award', '', '', '1', '2016-10-04 11:00:50', 'active'),
(44, 43, 'Award', 'award', '1,2,3,4', '', '0', '2016-10-04 11:00:50', 'active'),
(45, 0, 'Manage Menu', 'menu', '', '', '1', '2016-10-04 11:00:50', 'active'),
(46, 45, 'Manage Menu', 'menu', '1,2,3,4,5', '', '0', '2016-10-04 11:00:50', 'active'),
(47, 0, 'Manage Story', 'story', '', '', '1', '2016-10-04 11:00:50', 'active'),
(48, 47, 'Manage Story', 'story', '3', '', '0', '2016-10-04 11:00:50', 'active'),
(49, 0, 'Become Chef Seller', 'become_chef_seller', '', '', '1', '2016-10-04 11:00:50', 'active'),
(50, 49, 'Become Chef Seller', 'become_chef_seller', '3', '', '0', '2016-10-04 11:00:50', 'active'),
(51, 0, 'Manage Contactus', 'managecontactus', '', '', '0', '2016-10-04 11:00:50', 'active'),
(52, 51, 'Contactus', 'contactus', '3,4', '', '0', '2016-10-04 11:00:50', 'active'),
(53, 0, 'Manage Newbee', 'managenewbee', '', '', '0', '2016-10-04 11:00:50', 'active'),
(54, 53, 'Newbee', 'newbee', '3,4', '', '0', '2016-10-04 11:00:50', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `fs_order`
--

CREATE TABLE IF NOT EXISTS `fs_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `total_amount` float(12,2) NOT NULL,
  `tax_amount` float(12,2) NOT NULL,
  `vendor_discount` float(12,2) NOT NULL COMMENT 'in %',
  `vendor_discount_amount` float(12,2) NOT NULL,
  `coupon_code_id` int(11) NOT NULL,
  `coupon_code_percent` float(12,2) NOT NULL,
  `coupon_code_amount` float(12,2) NOT NULL,
  `final_amount` float(12,2) NOT NULL,
  `delivery_charge` int(11) NOT NULL,
  `status` varchar(255) NOT NULL COMMENT 'this will be from order_status table',
  `cancel_reason` varchar(255) NOT NULL,
  `cancel_by` bigint(19) NOT NULL,
  `total_items` int(11) NOT NULL,
  `delivery_type` enum('1','2') NOT NULL COMMENT '1=>delivery,2=>take away',
  `is_as_soon_or_later` enum('1','2') NOT NULL COMMENT '1=>as soon as possible,2=>later',
  `delivery_date` date NOT NULL,
  `delivery_time` time NOT NULL,
  `order_message` text NOT NULL,
  `payment_status` enum('1','2') NOT NULL COMMENT '1=transaction success,2-transaction fail',
  `delivery_country` int(11) NOT NULL,
  `delivery_state` int(11) NOT NULL,
  `delivery_city` int(11) NOT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `delivery_zipcode` varchar(10) NOT NULL,
  `delivery_landmark` varchar(255) NOT NULL,
  `delivery_instruction` text NOT NULL,
  `delivery_lattitude` varchar(255) NOT NULL,
  `delivery_longitude` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=49 ;

--
-- Dumping data for table `fs_order`
--

INSERT INTO `fs_order` (`id`, `user_id`, `vendor_id`, `address_id`, `total_amount`, `tax_amount`, `vendor_discount`, `vendor_discount_amount`, `coupon_code_id`, `coupon_code_percent`, `coupon_code_amount`, `final_amount`, `delivery_charge`, `status`, `cancel_reason`, `cancel_by`, `total_items`, `delivery_type`, `is_as_soon_or_later`, `delivery_date`, `delivery_time`, `order_message`, `payment_status`, `delivery_country`, `delivery_state`, `delivery_city`, `delivery_address`, `delivery_zipcode`, `delivery_landmark`, `delivery_instruction`, `delivery_lattitude`, `delivery_longitude`, `created_date`, `updated_date`) VALUES
(1, 20, 19, 1, 44.00, 0.44, 0.00, 0.00, 0, 0.00, 0.00, 44.44, 0, '4', '', 0, 2, '1', '1', '0000-00-00', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-04 15:40:41', '2017-12-04 12:20:34'),
(2, 20, 19, 1, 4.00, 0.04, 0.00, 0.00, 0, 0.00, 0.00, 4.04, 0, '5', '', 0, 1, '1', '1', '2017-12-04', '00:00:00', 'Order Address not specified.', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-04 16:58:01', '2017-12-04 12:21:44'),
(3, 20, 19, 1, 63.00, 0.63, 0.00, 0.00, 0, 0.00, 0.00, 63.63, 0, '5', '', 0, 1, '1', '2', '2017-12-05', '03:00:00', 'ok ', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-04 16:58:26', '2017-12-04 13:49:17'),
(4, 20, 19, 1, 44.00, 0.44, 0.00, 0.00, 0, 0.00, 0.00, 44.44, 0, '1', '', 0, 2, '1', '2', '2017-12-04', '01:30:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-04 17:19:43', '2017-12-04 12:18:45'),
(5, 20, 19, 1, 151.00, 1.51, 0.00, 0.00, 0, 0.00, 0.00, 152.51, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 10:01:18', '2017-12-12 04:31:18'),
(6, 20, 19, 1, 147.00, 1.47, 0.00, 0.00, 0, 0.00, 0.00, 148.47, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 10:08:59', '2017-12-12 04:38:59'),
(7, 20, 19, 1, 147.00, 1.47, 0.00, 0.00, 0, 0.00, 0.00, 148.47, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 10:49:20', '2017-12-12 05:19:20'),
(8, 20, 19, 1, 189.00, 1.89, 0.00, 0.00, 0, 0.00, 0.00, 190.89, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 10:51:46', '2017-12-12 05:21:46'),
(9, 20, 19, 1, 147.00, 1.47, 0.00, 0.00, 0, 0.00, 0.00, 148.47, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 11:09:10', '2017-12-12 05:39:10'),
(10, 20, 19, 1, 168.00, 1.68, 0.00, 0.00, 0, 0.00, 0.00, 169.68, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 11:12:45', '2017-12-12 05:42:45'),
(11, 20, 19, 1, 210.00, 2.10, 0.00, 0.00, 0, 0.00, 0.00, 212.10, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 11:17:56', '2017-12-12 05:47:56'),
(12, 20, 19, 1, 168.00, 1.68, 0.00, 0.00, 0, 0.00, 0.00, 169.68, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:10:30', '2017-12-12 06:40:30'),
(13, 20, 19, 1, 168.00, 1.68, 0.00, 0.00, 0, 0.00, 0.00, 169.68, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:11:18', '2017-12-12 06:41:18'),
(14, 20, 19, 1, 169.00, 1.69, 0.00, 0.00, 0, 0.00, 0.00, 170.69, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:13:35', '2017-12-12 06:43:35'),
(15, 20, 19, 1, 169.00, 1.69, 0.00, 0.00, 0, 0.00, 0.00, 170.69, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:13:56', '2017-12-12 06:43:56'),
(16, 20, 19, 1, 169.00, 1.69, 0.00, 0.00, 0, 0.00, 0.00, 170.69, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:25:38', '2017-12-12 06:55:38'),
(17, 20, 19, 1, 274.00, 2.74, 0.00, 0.00, 0, 0.00, 0.00, 276.74, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:26:14', '2017-12-12 06:56:14'),
(18, 20, 19, 1, 274.00, 2.74, 0.00, 0.00, 0, 0.00, 0.00, 276.74, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:26:36', '2017-12-12 06:56:36'),
(19, 20, 19, 1, 274.00, 2.74, 0.00, 0.00, 0, 0.00, 0.00, 276.74, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:27:56', '2017-12-12 06:57:56'),
(20, 20, 19, 1, 168.00, 1.68, 0.00, 0.00, 0, 0.00, 0.00, 169.68, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 12:28:19', '2017-12-12 06:58:19'),
(21, 20, 19, 1, 147.00, 1.47, 0.00, 0.00, 0, 0.00, 0.00, 148.47, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 13:16:11', '2017-12-12 07:46:11'),
(22, 20, 19, 1, 231.00, 2.31, 0.00, 0.00, 0, 0.00, 0.00, 233.31, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 13:55:47', '2017-12-12 08:25:47'),
(23, 20, 19, 1, 189.00, 1.89, 0.00, 0.00, 0, 0.00, 0.00, 190.89, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 14:47:20', '2017-12-12 09:17:20'),
(24, 20, 19, 1, 189.00, 1.89, 0.00, 0.00, 0, 0.00, 0.00, 190.89, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 14:49:54', '2017-12-12 09:19:54'),
(25, 20, 19, 1, 168.00, 1.68, 0.00, 0.00, 0, 0.00, 0.00, 169.68, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 14:55:10', '2017-12-12 09:25:10'),
(26, 20, 19, 1, 147.00, 1.47, 0.00, 0.00, 0, 0.00, 0.00, 148.47, 0, '1', '', 0, 1, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 16:58:28', '2017-12-12 11:28:28'),
(27, 20, 19, 1, 217.00, 2.17, 0.00, 0.00, 0, 0.00, 0.00, 219.17, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:31:43', '2017-12-12 13:01:43'),
(28, 20, 19, 1, 196.00, 1.96, 0.00, 0.00, 0, 0.00, 0.00, 197.96, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:34:37', '2017-12-12 13:04:37'),
(29, 20, 19, 1, 367.00, 3.67, 0.00, 0.00, 0, 0.00, 0.00, 370.67, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:44:50', '2017-12-12 13:14:50'),
(30, 20, 19, 1, 151.00, 1.51, 0.00, 0.00, 0, 0.00, 0.00, 152.51, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:46:10', '2017-12-12 13:16:10'),
(31, 20, 19, 1, 153.00, 1.53, 0.00, 0.00, 0, 0.00, 0.00, 154.53, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:49:34', '2017-12-12 13:19:34'),
(32, 20, 19, 1, 175.00, 1.75, 0.00, 0.00, 0, 0.00, 0.00, 176.75, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:53:43', '2017-12-12 13:23:43'),
(33, 20, 19, 1, 175.00, 1.75, 0.00, 0.00, 0, 0.00, 0.00, 176.75, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 18:55:45', '2017-12-12 13:25:45'),
(34, 20, 19, 1, 132.00, 1.32, 0.00, 0.00, 0, 0.00, 0.00, 133.32, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:01:26', '2017-12-12 13:31:26'),
(35, 20, 19, 1, 172.00, 1.72, 0.00, 0.00, 0, 0.00, 0.00, 173.72, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:06:37', '2017-12-12 13:36:37'),
(36, 20, 19, 1, 150.00, 1.50, 0.00, 0.00, 0, 0.00, 0.00, 151.50, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:17:43', '2017-12-12 13:47:43'),
(37, 20, 19, 1, 171.00, 1.71, 0.00, 0.00, 0, 0.00, 0.00, 172.71, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:25:38', '2017-12-12 13:55:38'),
(38, 20, 19, 1, 171.00, 1.71, 0.00, 0.00, 0, 0.00, 0.00, 172.71, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:25:57', '2017-12-12 13:55:57'),
(39, 20, 19, 1, 171.00, 1.71, 0.00, 0.00, 0, 0.00, 0.00, 172.71, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:26:07', '2017-12-12 13:56:07'),
(40, 20, 19, 1, 171.00, 1.71, 0.00, 0.00, 0, 0.00, 0.00, 172.71, 0, '1', '', 0, 2, '1', '1', '2017-12-12', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-12 19:27:43', '2017-12-12 13:57:43'),
(41, 22, 19, 1, 173.00, 1.73, 0.00, 0.00, 0, 0.00, 0.00, 174.73, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 10:15:29', '2017-12-13 04:45:29'),
(42, 22, 19, 1, 174.00, 1.74, 0.00, 0.00, 0, 0.00, 0.00, 175.74, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 10:31:59', '2017-12-13 05:01:59'),
(43, 22, 19, 1, 216.00, 2.16, 0.00, 0.00, 0, 0.00, 0.00, 218.16, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 10:49:32', '2017-12-13 05:19:32'),
(44, 22, 19, 1, 171.00, 1.71, 0.00, 0.00, 0, 0.00, 0.00, 172.71, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 10:51:50', '2017-12-13 05:21:50'),
(45, 22, 19, 1, 130.00, 1.30, 0.00, 0.00, 0, 0.00, 0.00, 131.30, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 10:55:19', '2017-12-13 05:25:19'),
(46, 22, 19, 1, 174.00, 1.74, 0.00, 0.00, 0, 0.00, 0.00, 175.74, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 10:57:32', '2017-12-13 05:27:32'),
(47, 22, 19, 1, 172.00, 1.72, 0.00, 0.00, 0, 0.00, 0.00, 173.72, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 11:07:58', '2017-12-13 05:37:58'),
(48, 22, 19, 1, 193.00, 1.93, 0.00, 0.00, 0, 0.00, 0.00, 194.93, 0, '1', '', 0, 2, '1', '1', '2017-12-13', '00:00:00', '', '', 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '25.759525753742587', '60.516000000000076', '2017-12-13 11:12:29', '2017-12-13 05:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `fs_order_details`
--

CREATE TABLE IF NOT EXISTS `fs_order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_varient_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` float(10,2) NOT NULL,
  `total_price_after_discount` float(12,2) NOT NULL,
  `total_price` float(10,2) NOT NULL,
  `coupon_code_id` int(11) NOT NULL,
  `coupon_code_percent` float(12,2) NOT NULL,
  `coupon_code_amount` float(12,2) NOT NULL,
  `tax` float(10,2) NOT NULL COMMENT 'in %',
  `tax_amount` float(10,2) NOT NULL,
  `final_amount` float(10,2) NOT NULL,
  `unit_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=78 ;

--
-- Dumping data for table `fs_order_details`
--

INSERT INTO `fs_order_details` (`id`, `order_id`, `menu_varient_id`, `quantity`, `unit_price`, `total_price_after_discount`, `total_price`, `coupon_code_id`, `coupon_code_percent`, `coupon_code_amount`, `tax`, `tax_amount`, `final_amount`, `unit_id`) VALUES
(1, 1, 1, 2, 1.00, 0.00, 2.00, 0, 0.00, 0.00, 1.00, 0.02, 1.98, 0),
(2, 1, 2, 2, 21.00, 0.00, 42.00, 0, 0.00, 0.00, 1.00, 0.42, 41.58, 0),
(3, 2, 1, 4, 1.00, 0.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(4, 3, 2, 3, 21.00, 0.00, 63.00, 0, 0.00, 0.00, 1.00, 0.63, 62.37, 0),
(5, 4, 2, 2, 21.00, 0.00, 42.00, 0, 0.00, 0.00, 1.00, 0.42, 41.58, 0),
(6, 4, 1, 2, 1.00, 0.00, 2.00, 0, 0.00, 0.00, 1.00, 0.02, 1.98, 0),
(7, 5, 1, 4, 1.00, 4.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(8, 5, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(9, 6, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(10, 7, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(11, 8, 2, 9, 21.00, 189.00, 189.00, 0, 0.00, 0.00, 1.00, 1.89, 187.11, 0),
(12, 9, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(13, 10, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(14, 11, 2, 10, 21.00, 210.00, 210.00, 0, 0.00, 0.00, 1.00, 2.10, 207.90, 0),
(15, 14, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(16, 14, 1, 1, 1.00, 1.00, 1.00, 0, 0.00, 0.00, 1.00, 0.01, 0.99, 0),
(17, 15, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(18, 15, 1, 1, 1.00, 1.00, 1.00, 0, 0.00, 0.00, 1.00, 0.01, 0.99, 0),
(19, 16, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(20, 16, 1, 1, 1.00, 1.00, 1.00, 0, 0.00, 0.00, 1.00, 0.01, 0.99, 0),
(21, 17, 2, 13, 21.00, 273.00, 273.00, 0, 0.00, 0.00, 1.00, 2.73, 270.27, 0),
(22, 17, 1, 1, 1.00, 1.00, 1.00, 0, 0.00, 0.00, 1.00, 0.01, 0.99, 0),
(23, 18, 2, 13, 21.00, 273.00, 273.00, 0, 0.00, 0.00, 1.00, 2.73, 270.27, 0),
(24, 18, 1, 1, 1.00, 1.00, 1.00, 0, 0.00, 0.00, 1.00, 0.01, 0.99, 0),
(25, 19, 2, 13, 21.00, 273.00, 273.00, 0, 0.00, 0.00, 1.00, 2.73, 270.27, 0),
(26, 19, 1, 1, 1.00, 1.00, 1.00, 0, 0.00, 0.00, 1.00, 0.01, 0.99, 0),
(27, 20, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(28, 21, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(29, 22, 2, 11, 21.00, 231.00, 231.00, 0, 0.00, 0.00, 1.00, 2.31, 228.69, 0),
(30, 23, 2, 9, 21.00, 189.00, 189.00, 0, 0.00, 0.00, 1.00, 1.89, 187.11, 0),
(31, 24, 2, 9, 21.00, 189.00, 189.00, 0, 0.00, 0.00, 1.00, 1.89, 187.11, 0),
(32, 25, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(33, 26, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(34, 27, 1, 7, 1.00, 7.00, 7.00, 0, 0.00, 0.00, 1.00, 0.07, 6.93, 0),
(35, 27, 2, 10, 21.00, 210.00, 210.00, 0, 0.00, 0.00, 1.00, 2.10, 207.90, 0),
(36, 28, 1, 7, 1.00, 7.00, 7.00, 0, 0.00, 0.00, 1.00, 0.07, 6.93, 0),
(37, 28, 2, 9, 21.00, 189.00, 189.00, 0, 0.00, 0.00, 1.00, 1.89, 187.11, 0),
(38, 29, 1, 10, 1.00, 10.00, 10.00, 0, 0.00, 0.00, 1.00, 0.10, 9.90, 0),
(39, 29, 2, 17, 21.00, 357.00, 357.00, 0, 0.00, 0.00, 1.00, 3.57, 353.43, 0),
(40, 30, 1, 4, 1.00, 4.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(41, 30, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(42, 31, 1, 6, 1.00, 6.00, 6.00, 0, 0.00, 0.00, 1.00, 0.06, 5.94, 0),
(43, 31, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(44, 32, 1, 7, 1.00, 7.00, 7.00, 0, 0.00, 0.00, 1.00, 0.07, 6.93, 0),
(45, 32, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(46, 33, 1, 7, 1.00, 7.00, 7.00, 0, 0.00, 0.00, 1.00, 0.07, 6.93, 0),
(47, 33, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(48, 34, 1, 6, 1.00, 6.00, 6.00, 0, 0.00, 0.00, 1.00, 0.06, 5.94, 0),
(49, 34, 2, 6, 21.00, 126.00, 126.00, 0, 0.00, 0.00, 1.00, 1.26, 124.74, 0),
(50, 35, 1, 4, 1.00, 4.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(51, 35, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(52, 36, 1, 3, 1.00, 3.00, 3.00, 0, 0.00, 0.00, 1.00, 0.03, 2.97, 0),
(53, 36, 2, 7, 21.00, 147.00, 147.00, 0, 0.00, 0.00, 1.00, 1.47, 145.53, 0),
(54, 37, 1, 3, 1.00, 3.00, 3.00, 0, 0.00, 0.00, 1.00, 0.03, 2.97, 0),
(55, 37, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(56, 38, 1, 3, 1.00, 3.00, 3.00, 0, 0.00, 0.00, 1.00, 0.03, 2.97, 0),
(57, 38, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(58, 39, 1, 3, 1.00, 3.00, 3.00, 0, 0.00, 0.00, 1.00, 0.03, 2.97, 0),
(59, 39, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(60, 40, 1, 3, 1.00, 3.00, 3.00, 0, 0.00, 0.00, 1.00, 0.03, 2.97, 0),
(61, 40, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(62, 41, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(63, 41, 1, 5, 1.00, 5.00, 5.00, 0, 0.00, 0.00, 1.00, 0.05, 4.95, 0),
(64, 42, 1, 6, 1.00, 6.00, 6.00, 0, 0.00, 0.00, 1.00, 0.06, 5.94, 0),
(65, 42, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(66, 43, 1, 6, 1.00, 6.00, 6.00, 0, 0.00, 0.00, 1.00, 0.06, 5.94, 0),
(67, 43, 2, 10, 21.00, 210.00, 210.00, 0, 0.00, 0.00, 1.00, 2.10, 207.90, 0),
(68, 44, 1, 3, 1.00, 3.00, 3.00, 0, 0.00, 0.00, 1.00, 0.03, 2.97, 0),
(69, 44, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(70, 45, 1, 4, 1.00, 4.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(71, 45, 2, 6, 21.00, 126.00, 126.00, 0, 0.00, 0.00, 1.00, 1.26, 124.74, 0),
(72, 46, 1, 6, 1.00, 6.00, 6.00, 0, 0.00, 0.00, 1.00, 0.06, 5.94, 0),
(73, 46, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(74, 47, 1, 4, 1.00, 4.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(75, 47, 2, 8, 21.00, 168.00, 168.00, 0, 0.00, 0.00, 1.00, 1.68, 166.32, 0),
(76, 48, 1, 4, 1.00, 4.00, 4.00, 0, 0.00, 0.00, 1.00, 0.04, 3.96, 0),
(77, 48, 2, 9, 21.00, 189.00, 189.00, 0, 0.00, 0.00, 1.00, 1.89, 187.11, 0);

-- --------------------------------------------------------

--
-- Table structure for table `fs_order_status`
--

CREATE TABLE IF NOT EXISTS `fs_order_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `fs_order_status`
--

INSERT INTO `fs_order_status` (`id`, `order_type`) VALUES
(1, 'Pending'),
(2, 'Confirm'),
(3, 'In Transit'),
(4, 'Delivered'),
(5, 'Cancel');

-- --------------------------------------------------------

--
-- Table structure for table `fs_permissions`
--

CREATE TABLE IF NOT EXISTS `fs_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `submodule` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `module` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `module_id` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `fs_questions`
--

CREATE TABLE IF NOT EXISTS `fs_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `fs_questions`
--

INSERT INTO `fs_questions` (`id`, `question`, `status`, `created_date`, `updated_date`) VALUES
(1, 'What is your Dietary Preference?', 'active', '0000-00-00 00:00:00', '2018-03-10 19:17:09'),
(2, 'What is your age? ', 'active', '0000-00-00 00:00:00', '2018-03-10 19:17:09'),
(3, 'Do you have any allergies? ', 'active', '0000-00-00 00:00:00', '2018-03-10 19:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `fs_question_attempt`
--

CREATE TABLE IF NOT EXISTS `fs_question_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `question_ids` varchar(255) NOT NULL COMMENT ', seprated question ids',
  `answer_ids` varchar(255) NOT NULL COMMENT ', seprated answer id',
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `fs_question_options`
--

CREATE TABLE IF NOT EXISTS `fs_question_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `answer` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `fs_question_options`
--

INSERT INTO `fs_question_options` (`id`, `question_id`, `answer`) VALUES
(1, 1, 'Vegetarian'),
(2, 1, 'Vegan'),
(3, 1, 'Gluten Free'),
(4, 1, 'Pescetarian'),
(5, 1, 'Low-Calorie'),
(6, 2, '18-24'),
(7, 2, '25-39'),
(8, 2, '40-60'),
(9, 2, '60+'),
(10, 3, 'Nuts'),
(11, 3, 'Milk'),
(12, 3, 'Egg'),
(13, 3, 'Fish'),
(14, 3, 'Soy');

-- --------------------------------------------------------

--
-- Table structure for table `fs_roles`
--

CREATE TABLE IF NOT EXISTS `fs_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `fs_roles`
--

INSERT INTO `fs_roles` (`id`, `role_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Administrator ', 'active', '2016-10-04 00:00:00', '2016-10-04 05:26:09'),
(2, 'Analyst', 'active', '2016-12-01 13:26:00', '2016-12-01 07:56:00'),
(3, 'seller', 'active', '2017-12-19 05:42:38', '2017-12-19 04:42:38'),
(4, 'Test', 'active', '2017-12-19 17:18:36', '2017-12-19 11:48:36');

-- --------------------------------------------------------

--
-- Table structure for table `fs_roles_modules_mapping`
--

CREATE TABLE IF NOT EXISTS `fs_roles_modules_mapping` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `submoduel_id` int(11) NOT NULL,
  `method_id` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `fs_roles_modules_mapping`
--

INSERT INTO `fs_roles_modules_mapping` (`id`, `role_id`, `module_id`, `submoduel_id`, `method_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 13, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-14 08:54:19'),
(2, 1, 1, 14, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-18 12:35:49'),
(3, 2, 1, 13, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-18 12:33:48'),
(4, 2, 1, 18, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-18 12:37:52'),
(5, 2, 1, 15, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-18 12:49:30'),
(6, 2, 1, 14, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-18 12:35:59'),
(7, 2, 1, 16, '1,2,3', '0000-00-00 00:00:00', '2017-12-15 06:25:40'),
(8, 2, 1, 19, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-15 06:32:44'),
(9, 2, 1, 20, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-15 06:37:44'),
(10, 2, 1, 22, '2,3', '0000-00-00 00:00:00', '2017-12-15 06:56:32'),
(11, 2, 1, 17, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-18 12:34:43'),
(12, 2, 1, 21, '2,3,5', '0000-00-00 00:00:00', '2017-12-15 07:03:06'),
(13, 2, 2, 23, '2,3,5', '0000-00-00 00:00:00', '2017-12-15 07:10:55'),
(14, 2, 3, 24, '1,3,4,5,6,7', '0000-00-00 00:00:00', '2017-12-15 08:42:16'),
(15, 2, 3, 25, '3,8,9', '0000-00-00 00:00:00', '2017-12-15 08:51:40'),
(16, 2, 4, 26, '3,5,10', '0000-00-00 00:00:00', '2017-12-15 09:04:59'),
(17, 2, 5, 27, '1,3,5,10,11,12', '0000-00-00 00:00:00', '2017-12-19 12:29:01'),
(18, 2, 6, 28, '3,5,13,14', '0000-00-00 00:00:00', '2017-12-18 05:52:40'),
(19, 2, 8, 29, '1,2,3,4,5,10', '0000-00-00 00:00:00', '2017-12-18 05:54:43'),
(20, 2, 9, 30, '1,2,3,4,5,10,15', '0000-00-00 00:00:00', '2017-12-18 06:02:57'),
(21, 2, 5, 29, '3', '0000-00-00 00:00:00', '2017-12-18 05:53:28'),
(22, 2, 10, 31, '3,5,13', '0000-00-00 00:00:00', '2017-12-18 07:10:45'),
(23, 2, 11, 32, '16', '0000-00-00 00:00:00', '2017-12-18 07:17:07'),
(24, 2, 12, 34, '2,3', '0000-00-00 00:00:00', '2017-12-18 09:21:23'),
(25, 2, 12, 35, '1,2,3', '0000-00-00 00:00:00', '2017-12-18 09:26:18'),
(26, 2, 7, 37, '3,5,13', '0000-00-00 00:00:00', '2017-12-18 13:44:27'),
(27, 2, 7, 38, '3,5,13', '0000-00-00 00:00:00', '2017-12-18 13:46:58'),
(28, 2, 7, 36, '3,5,13', '0000-00-00 00:00:00', '2017-12-18 13:44:12'),
(29, 3, 4, 26, '3,5,10', '0000-00-00 00:00:00', '2017-12-19 04:45:32'),
(30, 3, 5, 27, '1,2,3,5,10,11,12', '0000-00-00 00:00:00', '2017-12-20 09:21:24'),
(31, 3, 39, 40, '1,2,3,4,5,10', '0000-00-00 00:00:00', '2017-12-19 05:23:13'),
(32, 3, 41, 42, '3', '0000-00-00 00:00:00', '2017-12-19 05:25:30'),
(33, 3, 43, 44, '1,2,3,4', '0000-00-00 00:00:00', '2017-12-19 05:33:56'),
(34, 3, 45, 46, '1,2,3,4,5', '0000-00-00 00:00:00', '2017-12-19 05:41:48'),
(35, 3, 47, 48, '3', '0000-00-00 00:00:00', '2017-12-19 05:43:43'),
(36, 3, 3, 24, '1,3,4,5,6,7', '0000-00-00 00:00:00', '2017-12-19 05:45:59'),
(37, 3, 49, 50, '3', '0000-00-00 00:00:00', '2017-12-19 05:51:07'),
(38, 4, 1, 15, '2,3', '0000-00-00 00:00:00', '2017-12-19 11:55:42'),
(39, 4, 5, 27, '1,3', '0000-00-00 00:00:00', '2017-12-19 12:27:31'),
(40, 4, 53, 54, '3,4', '0000-00-00 00:00:00', '2017-12-22 07:21:32'),
(41, 4, 51, 52, '3,4', '0000-00-00 00:00:00', '2017-12-22 07:21:39'),
(42, 2, 53, 54, '3,4', '0000-00-00 00:00:00', '2017-12-22 08:33:19'),
(43, 2, 53, 52, '3,4', '0000-00-00 00:00:00', '2017-12-22 08:33:32');

-- --------------------------------------------------------

--
-- Table structure for table `fs_state`
--

CREATE TABLE IF NOT EXISTS `fs_state` (
  `id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fs_state`
--

INSERT INTO `fs_state` (`id`, `country_id`, `name`, `status`, `created_date`, `updated_date`) VALUES
(1, 2, 'LA', 'active', '2017-10-26 14:37:55', '2018-03-17 17:53:29'),
(4, 2, 'sydney', 'active', '2017-10-26 14:39:11', '2018-03-17 17:53:26'),
(6, 2, 'Boston', 'active', '2017-11-01 16:30:28', '2018-03-17 17:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `fs_unit`
--

CREATE TABLE IF NOT EXISTS `fs_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `fs_unit`
--

INSERT INTO `fs_unit` (`id`, `name`, `status`, `created_date`, `updated_date`) VALUES
(1, 'length', 'active', '0000-00-00 00:00:00', '2018-03-18 03:44:24'),
(2, 'pcs', 'active', '0000-00-00 00:00:00', '2018-03-18 03:44:24'),
(3, 'plate', 'active', '0000-00-00 00:00:00', '2018-03-18 03:44:24');

-- --------------------------------------------------------

--
-- Table structure for table `fs_users`
--

CREATE TABLE IF NOT EXISTS `fs_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `food_stall_type` enum('1','2','3') NOT NULL COMMENT '1=>food truck, 2=> food stall, 3=> home made food',
  `status` enum('active','inactive','delete') NOT NULL DEFAULT 'inactive',
  `otp` varchar(255) NOT NULL,
  `is_verify` enum('0','1') NOT NULL,
  `is_profile_complete` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'is seller profile completed or not 0=> not completed,1=>completed',
  `is_approved` enum('0','1') NOT NULL DEFAULT '1' COMMENT '0=>not approved,1=>approved',
  `plan_status` enum('','active','expired') NOT NULL DEFAULT '',
  `plan_featured_id` int(11) NOT NULL,
  `plan_featured_end_date` date NOT NULL,
  `user_type` enum('1','2','3','4') NOT NULL COMMENT '1=admin,3-vendor,4-user',
  `role_id` int(11) NOT NULL COMMENT 'in which this user enrolled to',
  `profile_image` varchar(255) NOT NULL,
  `subscribe` enum('0','1') NOT NULL COMMENT '0=>"no",1=>"yes"',
  `is_awarded` enum('0','1') NOT NULL COMMENT '0=>non awarded,1=>awarded',
  `avg_rating` float(2,1) NOT NULL COMMENT 'this is average rating based on given user and it will be update when any user will give rating',
  `is_chef` enum('0','1') NOT NULL COMMENT '0=>no,1=>yes',
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime NOT NULL,
  `added_by` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `fs_users`
--

INSERT INTO `fs_users` (`id`, `first_name`, `last_name`, `email`, `mobile_number`, `password`, `food_stall_type`, `status`, `otp`, `is_verify`, `is_profile_complete`, `is_approved`, `plan_status`, `plan_featured_id`, `plan_featured_end_date`, `user_type`, `role_id`, `profile_image`, `subscribe`, `is_awarded`, `avg_rating`, `is_chef`, `created_date`, `updated_date`, `last_login`, `added_by`) VALUES
(1, 'super', 'Admin', 'yuumo@yopmail.com', '4444449444', 'e10adc3949ba59abbe56e057f20f883e', '', 'active', '', '', '0', '0', '', 2, '2018-07-18', '1', 1, '9ebdf673562de6b59319331f34999fc6_thumb.jpg', '0', '1', 0.0, '', '2017-10-24 00:00:00', '2018-05-26 19:15:53', '2018-05-26 07:15:53', 0),
(2, 'Arvindsoni', 'Soni', 'arvind@tekshapers.com', '8860233639', 'e10adc3949ba59abbe56e057f20f883e', '', 'active', '', '', '0', '0', 'expired', 0, '0000-00-00', '3', 2, '', '0', '1', 0.0, '', '2017-10-25 05:00:00', '2018-03-17 17:20:34', '2017-12-22 02:03:50', 0),
(3, 'dsfgh', 'dsfg', 'asdf@gmail.com', '2354356546', 'e10adc3949ba59abbe56e057f20f883e', '2', 'active', '', '1', '1', '1', '', 0, '0000-00-00', '3', 3, '', '0', '0', 0.0, '0', '2018-03-17 18:23:12', '2018-03-17 19:12:05', '2018-03-17 06:36:12', 1),
(4, 'pizza', 'pizza', 'pizza@yopmail.com', '1234567854', 'e10adc3949ba59abbe56e057f20f883e', '2', 'active', '', '1', '1', '1', '', 0, '0000-00-00', '3', 3, '', '0', '0', 0.0, '0', '2018-03-17 20:14:46', '2018-05-22 16:19:26', '2018-05-22 04:19:26', 1),
(5, 'Nripen', 'Kumar', '', '0987654321', 'e10adc3949ba59abbe56e057f20f883e', '1', 'active', '1234', '1', '0', '1', '', 0, '0000-00-00', '4', 0, '', '0', '0', 0.0, '0', '0000-00-00 00:00:00', '2018-05-24 10:20:06', '0000-00-00 00:00:00', 0),
(6, 'Jjo', 'Ooi', '', '0123456789', 'e10adc3949ba59abbe56e057f20f883e', '1', 'active', '1234', '1', '0', '1', '', 0, '0000-00-00', '4', 0, '', '0', '0', 0.0, '0', '0000-00-00 00:00:00', '2018-05-24 10:22:43', '0000-00-00 00:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `fs_users_details`
--

CREATE TABLE IF NOT EXISTS `fs_users_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `landline_number` varchar(255) NOT NULL,
  `alternate_number` varchar(255) NOT NULL,
  `food_joint_name` varchar(255) NOT NULL,
  `food_type` int(11) NOT NULL COMMENT '1=>''veg'',2=>''non veg'',3=>''both''',
  `about` text NOT NULL,
  `logo_image` varchar(255) NOT NULL,
  `banner_image` varchar(255) NOT NULL,
  `year_of_inception` varchar(255) NOT NULL,
  `deliver_food_at_home` enum('0','1') NOT NULL COMMENT '0=>no, 1=> yes',
  `provide_catering_event` enum('0','1') NOT NULL COMMENT '0=>no, 1=> yes',
  `take_order_for_take_away` enum('0','1') NOT NULL COMMENT '0=>no, 1=> yes',
  `delivery_fee_applicable` enum('0','1') NOT NULL COMMENT '0=>no 1=>yes (no=>free,yes=>chargeble)',
  `delivery_fee_applicable_for` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '0=>none, 1=> all order,2=>order less than min order',
  `delivery_fee` varchar(255) NOT NULL,
  `delivery_estimated_time` time NOT NULL,
  `minimum_order` varchar(255) NOT NULL,
  `discount` int(11) NOT NULL COMMENT 'in %',
  `distance_radius` varchar(50) NOT NULL COMMENT 'in km',
  `open_days` varchar(255) NOT NULL COMMENT 'all days name , seprated',
  `business_hour_open_time` time NOT NULL,
  `business_hour_close_time` time NOT NULL,
  `last_order_time_for_delivery` time NOT NULL,
  `website_url` varchar(255) NOT NULL,
  `country` int(11) NOT NULL,
  `state` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `zipcode` varchar(55) NOT NULL,
  `landmark` varchar(255) NOT NULL,
  `lattitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `google_search_address` varchar(255) NOT NULL COMMENT 'Google Search address',
  `highlight` text NOT NULL,
  `story_title` varchar(255) NOT NULL,
  `story_description` text NOT NULL,
  `oi_beneficiary_name` varchar(255) NOT NULL,
  `oi_bank_name` varchar(255) NOT NULL,
  `oi_bank_location` varchar(255) NOT NULL,
  `oi_account_number` varchar(255) NOT NULL,
  `oi_ifsc_code` varchar(255) NOT NULL,
  `oi_facebook_url` varchar(255) NOT NULL,
  `oi_instgram_id` varchar(255) NOT NULL,
  `oi_foodstall_id` varchar(255) NOT NULL,
  `sct_gst_number` varchar(255) NOT NULL,
  `sct_pan_number` varchar(255) NOT NULL,
  `sct_tin_number` varchar(255) NOT NULL,
  `sct_cin_number` varchar(255) NOT NULL,
  `pm_active_plan_id` int(11) NOT NULL,
  `pm_plan_start_date` datetime NOT NULL,
  `pm_plan_expire_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `fs_users_details`
--

INSERT INTO `fs_users_details` (`id`, `user_id`, `landline_number`, `alternate_number`, `food_joint_name`, `food_type`, `about`, `logo_image`, `banner_image`, `year_of_inception`, `deliver_food_at_home`, `provide_catering_event`, `take_order_for_take_away`, `delivery_fee_applicable`, `delivery_fee_applicable_for`, `delivery_fee`, `delivery_estimated_time`, `minimum_order`, `discount`, `distance_radius`, `open_days`, `business_hour_open_time`, `business_hour_close_time`, `last_order_time_for_delivery`, `website_url`, `country`, `state`, `city`, `address`, `zipcode`, `landmark`, `lattitude`, `longitude`, `google_search_address`, `highlight`, `story_title`, `story_description`, `oi_beneficiary_name`, `oi_bank_name`, `oi_bank_location`, `oi_account_number`, `oi_ifsc_code`, `oi_facebook_url`, `oi_instgram_id`, `oi_foodstall_id`, `sct_gst_number`, `sct_pan_number`, `sct_tin_number`, `sct_cin_number`, `pm_active_plan_id`, `pm_plan_start_date`, `pm_plan_expire_date`, `updated_date`) VALUES
(1, 3, '11111111111', '1111111111', 'dfgfhgjk', 2, 'asd', '', '', '2000', '0', '0', '0', '0', '0', '', '00:15:00', '100', 0, '1900', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday', '00:00:00', '00:00:00', '00:00:00', 'http://www.google.com', 2, 4, 4, 'qwertyu', '345671', 'jfg', '28.5355161', '77.39102649999995', 'Noida, Uttar Pradesh, India', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2018-03-17 19:09:21'),
(4, 2, '', '', 'Khushboo', 2, '', '9df065ed32025253ea21d583e66abe09.jpg', '', '1998', '1', '0', '0', '1', '2', '100', '00:30:00', '', 20, '1000', 'Monday,Tuesday,Wednesday,Thursday,Friday', '06:00:00', '17:00:00', '07:00:00', '', 1, 5, 6, 'test', '201001', 'test', '28.5355161', '77.39102649999995', 'Noida, Uttar Pradesh, India', '', '', '', 'Name', 'ICICI', 'Noida', '85486954684568458', '87687898', '', '', '', 'addas42343', '', '', '', 1, '2017-12-01 18:35:59', '2018-12-01 18:35:59', '2018-03-17 17:19:55'),
(5, 4, '11111111111', '1111111111', 'pizza', 1, '1dff', '5f135837f6ef02570f3b39c5d0b10e92.png', '', '2002', '0', '0', '0', '0', '0', '', '00:15:00', '100', 0, '1900', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday', '00:00:00', '00:00:00', '00:00:00', 'http://www.google.com', 2, 4, 4, 'sfdgfgb', '111111', 'fgfg', '28.5355161', '77.39102649999995', 'Noida, Uttar Pradesh, India', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2018-03-18 03:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `fs_user_addresses`
--

CREATE TABLE IF NOT EXISTS `fs_user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `zipcode` varchar(255) NOT NULL,
  `landmark` varchar(255) NOT NULL,
  `delivery_instruction` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `lattitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fs_user_addresses`
--

INSERT INTO `fs_user_addresses` (`id`, `user_id`, `country_id`, `state_id`, `city_id`, `address`, `zipcode`, `landmark`, `delivery_instruction`, `location`, `lattitude`, `longitude`, `created_date`, `updated_date`) VALUES
(1, 22, 1, 5, 6, 'dasdas', 'adsdas', 'adsdasd', 'asdasd', '', '25.759525753742587', '60.516000000000076', '0000-00-00 00:00:00', '2017-12-13 04:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `fs_user_chef`
--

CREATE TABLE IF NOT EXISTS `fs_user_chef` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `experience_of_cooking` enum('yes','no') NOT NULL,
  `years_of_experience` varchar(255) NOT NULL,
  `profile_type` varchar(255) NOT NULL,
  `is_approve` enum('approve','disapprove','pending') NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fs_user_chef`
--

INSERT INTO `fs_user_chef` (`id`, `user_id`, `experience_of_cooking`, `years_of_experience`, `profile_type`, `is_approve`, `status`, `created_date`, `updated_date`) VALUES
(3, 19, 'yes', '1-5', 'fresher', 'approve', 'active', '2017-12-06 10:22:26', '2017-12-07 09:17:38'),
(4, 85, 'yes', '0-1', 'fresher', 'disapprove', 'active', '2017-12-06 17:02:28', '2017-12-06 11:32:28'),
(5, 22, 'no', '', '', 'disapprove', 'active', '2017-12-07 11:55:48', '2017-12-07 06:25:48'),
(6, 114, 'yes', '5-10', 'experienced', 'approve', 'active', '2017-12-07 12:14:03', '2017-12-14 06:16:19'),
(7, 130, 'yes', '0-1', 'fresher', 'approve', 'active', '2017-12-13 11:29:48', '2017-12-14 05:55:44'),
(8, 136, 'yes', '0-1', 'fresher', 'disapprove', 'active', '2017-12-13 15:41:45', '2017-12-14 06:19:14'),
(9, 127, 'no', '', '', 'disapprove', 'active', '2017-12-13 17:47:36', '2017-12-13 12:17:36'),
(10, 139, 'no', '20-30', 'fresher', 'disapprove', 'active', '2017-12-14 14:08:29', '2017-12-14 08:48:09'),
(11, 141, 'yes', '1-5', 'fresher', 'disapprove', 'active', '2017-12-14 16:23:02', '2017-12-14 12:53:17'),
(12, 146, 'yes', '5-10', 'experienced', 'disapprove', 'active', '2017-12-19 11:21:07', '2017-12-19 05:51:07'),
(13, 3, 'yes', '1-5', 'fresher', 'disapprove', 'active', '2017-12-19 17:37:31', '2017-12-19 12:07:31'),
(14, 150, 'yes', '1-5', 'experienced', 'approve', 'active', '2017-12-19 17:51:30', '2017-12-19 12:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `fs_user_cuisine_category_mapping`
--

CREATE TABLE IF NOT EXISTS `fs_user_cuisine_category_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `cuisine_cat_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=296 ;

--
-- Dumping data for table `fs_user_cuisine_category_mapping`
--

INSERT INTO `fs_user_cuisine_category_mapping` (`id`, `user_id`, `cuisine_cat_id`) VALUES
(282, 3, 2),
(283, 3, 34),
(293, 4, 38),
(294, 4, 2),
(295, 4, 34);

-- --------------------------------------------------------

--
-- Table structure for table `fs_user_featured_plan_history`
--

CREATE TABLE IF NOT EXISTS `fs_user_featured_plan_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `txn_id` varchar(255) NOT NULL,
  `feaured_plan_id` int(11) NOT NULL,
  `amount` float(10,2) NOT NULL,
  `payment_status` enum('success','failure') NOT NULL DEFAULT 'success',
  `payment_mode` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fs_user_featured_plan_history`
--

INSERT INTO `fs_user_featured_plan_history` (`id`, `user_id`, `txn_id`, `feaured_plan_id`, `amount`, `payment_status`, `payment_mode`, `start_date`, `end_date`) VALUES
(1, 16, 'XXXXXXXX', 1, 500.00, 'success', 'paypal', '2017-11-28', '2017-12-28'),
(2, 16, 'XXXXXXXX', 3, 500.00, 'success', 'paypal', '2017-11-28', '2018-01-12'),
(3, 85, 'XXXXXXXX', 3, 500.00, 'success', 'paypal', '2017-12-06', '2018-01-20'),
(4, 85, 'XXXXXXXX', 1, 500.00, 'success', 'paypal', '2017-12-06', '2017-12-21'),
(5, 85, 'XXXXXXXX', 3, 500.00, 'success', 'paypal', '2017-12-06', '2018-01-20'),
(6, 85, 'XXXXXXXX', 5, 500.00, 'success', 'paypal', '2017-12-06', '2018-02-19'),
(7, 130, 'XXXXXXXX', 3, 500.00, 'success', 'paypal', '2017-12-13', '2018-01-27'),
(8, 114, 'XXXXXXXX', 1, 500.00, 'success', 'paypal', '2017-12-14', '2017-12-29'),
(9, 114, 'XXXXXXXX', 1, 500.00, 'success', 'paypal', '2017-12-14', '2017-12-29'),
(10, 114, 'XXXXXXXX', 5, 500.00, 'success', 'paypal', '2017-12-14', '2018-02-27'),
(11, 141, 'XXXXXXXX', 2, 500.00, 'success', 'paypal', '2017-12-14', '2018-01-13'),
(12, 141, 'XXXXXXXX', 3, 500.00, 'success', 'paypal', '2017-12-14', '2018-01-28'),
(13, 141, 'XXXXXXXX', 5, 500.00, 'success', 'paypal', '2017-12-14', '2018-02-27'),
(14, 141, 'XXXXXXXX', 6, 500.00, 'success', 'paypal', '2017-12-14', '2018-03-14'),
(15, 151, 'XXXXXXXX', 1, 500.00, 'success', 'paypal', '2017-12-19', '2018-01-03'),
(16, 150, 'XXXXXXXX', 4, 500.00, 'success', 'paypal', '2017-12-20', '2018-02-18'),
(17, 1, 'XXXXXXXX', 1, 500.00, 'success', 'paypal', '2017-12-20', '2018-01-04'),
(18, 1, 'XXXXXXXX', 2, 500.00, 'success', 'paypal', '2017-12-20', '2018-01-19'),
(19, 1, 'XXXXXXXX', 3, 500.00, 'success', 'paypal', '2017-12-20', '2018-02-03'),
(20, 1, 'XXXXXXXX', 6, 500.00, 'success', 'paypal', '2017-12-20', '2018-03-20'),
(21, 1, 'XXXXXXXX', 2, 500.00, 'success', 'paypal', '2017-12-20', '2018-01-19');

-- --------------------------------------------------------

--
-- Table structure for table `fs_user_plan_payment`
--

CREATE TABLE IF NOT EXISTS `fs_user_plan_payment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `plan_amount` int(11) NOT NULL,
  `pay_instant_or_later` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '0-no case,1-instanr, 2- later',
  `pay_online_or_bank_deposit` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '0-no case, 1-online,2-bank deposit',
  `op_card_number` varchar(255) NOT NULL,
  `op_name_on_card` varchar(255) NOT NULL,
  `op_cvv` varchar(255) NOT NULL,
  `op_expiry_date` varchar(255) NOT NULL,
  `bd_date` datetime NOT NULL,
  `bd_bank_name` varchar(255) NOT NULL,
  `bd_amount` varchar(255) NOT NULL,
  `bd_location` varchar(255) NOT NULL,
  `bd_cheque_number` varchar(255) NOT NULL,
  `bd_receipt_number` varchar(255) NOT NULL,
  `is_approve` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=>not approved,1=>approved',
  `payment_status` enum('success','failure') NOT NULL,
  `failure_message` text NOT NULL COMMENT 'this will be only for when admin reject bank deposit payment from bankend',
  `status` enum('active','inactive') NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fs_user_plan_payment`
--

INSERT INTO `fs_user_plan_payment` (`id`, `user_id`, `plan_id`, `plan_amount`, `pay_instant_or_later`, `pay_online_or_bank_deposit`, `op_card_number`, `op_name_on_card`, `op_cvv`, `op_expiry_date`, `bd_date`, `bd_bank_name`, `bd_amount`, `bd_location`, `bd_cheque_number`, `bd_receipt_number`, `is_approve`, `payment_status`, `failure_message`, `status`, `created_date`, `updated_date`) VALUES
(1, 5, 1, 500, '1', '1', '4111111111111111', 'aa', '123', '12/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-06 11:31:31'),
(2, 5, 1, 500, '1', '1', '4111111111111111', 'aa', '123', '12/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-06 11:31:31'),
(3, 6, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-06 13:25:09'),
(4, 6, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-28 09:43:39'),
(5, 7, 1, 500, '1', '1', '411111111111111111122', 'Archanafd', '123', '12/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 12:05:10'),
(6, 8, 1, 500, '1', '1', '411111111111111111122', 'aa', '123', '2/2023', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 06:05:57'),
(7, 8, 1, 500, '1', '1', '411111111111111111122', 'aa', '123', '2/2023', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:50:39'),
(8, 9, 1, 500, '1', '1', '56757575', '645645745', '123', '11/2021', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 12:03:25'),
(9, 11, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', '878', '78', '787', '78878', '77', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 07:13:13'),
(10, 11, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 07:36:44'),
(11, 11, 1, 500, '1', '1', '345435', 'svvxcv', '243', '9/2033', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 08:16:22'),
(12, 11, 1, 500, '1', '2', '', '', '', '', '2017-11-16 00:00:00', 'sDDAS', '2345', 'ASDASD', 'asdaSD', 'ASDASD', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 08:17:06'),
(13, 11, 1, 500, '1', '2', '', '', '', '', '2017-11-16 00:00:00', 'sDDAS', '2345', 'ASDASD', 'asdaSD', 'ASDASD', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 08:22:59'),
(14, 11, 1, 500, '1', '1', '343214234', 'fdsfsdf', '423', '3/2023', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 08:23:34'),
(15, 11, 1, 500, '1', '2', '', '', '', '', '2017-11-22 00:00:00', 'qdqweqw', '4324', 'dasd', 'q2432', 'dfsdf', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 12:00:37'),
(16, 11, 1, 500, '1', '2', '', '', '', '', '2017-11-22 00:00:00', 'qdqweqw', '4324', 'dasd', 'q2432', 'dfsdf', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 13:25:43'),
(17, 10, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 13:26:24'),
(18, 9, 1, 500, '1', '1', '56757575', '645645745', '123', '11/2021', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-07 12:04:21'),
(19, 9, 1, 500, '1', '1', '56757575', '645645745', '123', '11/2021', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-07 12:04:21'),
(20, 7, 1, 500, '1', '1', '411111111111111111122', 'Archanafd', '123', '12/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-07 12:05:10'),
(21, 4, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-07 12:06:43'),
(22, 3, 1, 500, '1', '1', '34124124243243432', 'FDFSDFDSF', '234', '4/2033', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-13 05:24:35'),
(23, 11, 1, 500, '1', '2', '', '', '', '', '2017-11-22 00:00:00', 'qdqweqw', '344', 'dasd', 'q2432', 'dfsdf', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-12 10:25:27'),
(24, 10, 1, 500, '1', '1', '24342342342432344', 'ADFASDASDA', '123', '7/2032', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-07 13:26:24'),
(25, 13, 1, 500, '1', '1', '4111111111111111', 'Archana', '123', '12/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-09 09:00:55'),
(26, 14, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-10 09:43:58'),
(27, 14, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-13 05:10:15'),
(28, 14, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-13 05:42:52'),
(29, 3, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', ' 3', '3', ' 3', ' 3', ' 3', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-24 13:28:36'),
(30, 14, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-13 06:32:29'),
(31, 14, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-13 06:32:29'),
(32, 19, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 07:03:32'),
(33, 3, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', ' 3', '3', ' 3', ' 3', ' 3', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-24 13:29:14'),
(34, 3, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', ' 3', '3', ' 3', ' 3', ' 3', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 07:26:39'),
(35, 15, 1, 500, '1', '1', '12521255422152', 'DHARMENDRA', '123', '9/2031', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-11 08:39:35'),
(36, 6, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-01 12:14:52'),
(37, 16, 1, 500, '1', '1', '4234323423423423', 'sdfsdfsdf', '222', '10/2032', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-11 13:11:59'),
(38, 29, 1, 500, '1', '1', '35345345345345345', 'Archana', '123', '11/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-29 09:45:55'),
(39, 29, 1, 500, '1', '1', '35345345345345345', 'Archana', '123', '11/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-29 10:09:02'),
(40, 29, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', 'Kotak', '6456464646', 'Noida', '456456464', '464646', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-29 10:11:09'),
(41, 29, 1, 500, '1', '1', '4111111111111111', 'Vendor', '123', '/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-29 10:11:09'),
(42, 19, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 07:04:05'),
(43, 19, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 07:09:03'),
(44, 19, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 07:10:01'),
(45, 19, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-04 09:54:30'),
(46, 3, 1, 500, '1', '2', '', '', '', '', '2017-11-30 00:00:00', ' 3', '3', ' 3', ' 3', ' 3', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 07:26:54'),
(47, 3, 1, 500, '1', '2', '', '', '', '', '2017-11-30 00:00:00', ' 3', '3', ' 3', ' 3', ' 3', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:32:06'),
(48, 3, 1, 500, '1', '2', '', '', '', '', '2017-11-30 00:00:00', ' 3', '3', ' 3', ' 3', ' 3', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:32:20'),
(49, 3, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:47:41'),
(50, 31, 1, 500, '1', '2', '', '', '', '', '2017-11-30 00:00:00', 'Kotak', '1000', 'Noida', '4747747', '6566', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:41:27'),
(51, 31, 1, 500, '1', '2', '', '', '', '', '2017-11-30 00:00:00', 'Kotak', '1000', 'Noida', '4747747', '6566', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:45:26'),
(52, 30, 1, 500, '1', '2', '', '', '', '', '2017-11-30 00:00:00', 'ftewer', '100', 'asds', 'asdasd', 'asdasd', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-13 06:32:56'),
(53, 31, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:46:18'),
(54, 31, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-30 11:46:18'),
(55, 3, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-11-30 11:51:02'),
(56, 8, 1, 500, '1', '1', '411111111111111111122', 'aa', '123', '2/2023', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-30 11:50:39'),
(57, 3, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-01 04:41:14'),
(58, 32, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-11-30 12:42:26'),
(59, 3, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-01 08:38:19'),
(60, 3, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-11 08:37:31'),
(61, 6, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', 'top123', '123', 'sagd452', 'asdfas24352345', 'asdf23452', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-01 13:03:43'),
(62, 6, 1, 500, '1', '2', '', '', '', '', '1970-01-01 00:00:00', 'top123', '123', 'sagd452', 'asdfas24352345', 'asdf23452', '0', 'failure', 'not authenticate ', 'inactive', '0000-00-00 00:00:00', '2017-12-01 13:05:59'),
(63, 6, 1, 500, '1', '2', '', '', '', '', '2017-12-01 00:00:00', 'top123', '123', 'sagd452', 'asdfas24352345', 'asdf23452', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-04 09:27:42'),
(64, 36, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-04 06:02:18'),
(65, 36, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-04 06:02:18'),
(66, 19, 1, 500, '1', '2', '', '', '', '', '2017-12-27 00:00:00', 'ewqeqw', '222', '22dsada', 'dasdasd', '22', '0', 'failure', '', 'inactive', '0000-00-00 00:00:00', '2017-12-04 09:58:45'),
(67, 19, 1, 500, '1', '2', '', '', '', '', '2017-12-28 00:00:00', 'dsds', '32', 'dsd', 'wee', 'dsds', '0', 'failure', '', 'inactive', '0000-00-00 00:00:00', '2017-12-04 09:59:19'),
(68, 19, 1, 500, '1', '2', '', '', '', '', '2017-12-29 00:00:00', 'PNB', '1212', 'fDF', '5445', '5464', '0', 'failure', '', 'inactive', '0000-00-00 00:00:00', '2017-12-04 10:04:32'),
(69, 19, 1, 500, '1', '1', '324234', '2344234', '232', '9/2033', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-04 10:06:54'),
(70, 19, 1, 500, '1', '1', '324234', '2344234', '232', '9/2033', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-08 12:03:44'),
(71, 64, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-05 08:31:53'),
(72, 12, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-05 12:22:45'),
(73, 85, 1, 500, '1', '1', '531551651511', '41585451521', '323', '3/2031', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-11 13:29:08'),
(74, 59, 1, 500, '1', '1', '411111111111111', 'Archana', '123', '/2020', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-06 12:49:44'),
(75, 103, 1, 500, '1', '2', '', '', '', '', '2017-12-07 00:00:00', 'top123', '123', 'sagd452', 'asdfas24352345', 'asdf23452', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-12 10:26:10'),
(76, 114, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-07 06:45:22'),
(77, 114, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-07 06:48:54'),
(78, 114, 1, 500, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-11 09:44:36'),
(79, 17, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-08 05:44:36'),
(80, 19, 1, 500, '1', '1', '324234', '2344234', '232', '9/2033', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-08 17:33:44', '2017-12-08 12:03:44'),
(81, 3, 2, 500, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-11 08:37:31'),
(82, 15, 1, 500, '1', '1', '12521255422152', 'DHARMENDRA', '123', '9/2031', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '2017-12-11 14:09:35', '2017-12-11 08:42:28'),
(83, 15, 1, 500, '1', '1', '12521255422152', 'DHARMENDRA', '123', '9/2031', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'active', '2017-12-11 14:12:28', '2017-12-11 08:42:28'),
(84, 114, 1, 56, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '2017-12-11 15:14:35', '2017-12-11 09:44:59'),
(85, 114, 1, 56, '1', '1', '1212121212', 'sdcfsd', '121', '2/2031', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-11 15:14:59', '2017-12-11 09:58:37'),
(86, 114, 2, 414, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-11 10:00:43'),
(87, 114, 1, 56, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '0', 'success', '', 'inactive', '2017-12-11 15:30:43', '2017-12-11 10:23:37'),
(88, 114, 2, 414, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-11 10:23:37'),
(89, 16, 1, 5, '1', '1', '4234323423423423', 'sdfsdfsdf', '222', '10/2032', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-11 18:41:58', '2017-12-11 13:11:59'),
(90, 85, 1, 5, '1', '1', '531551651511', '41585451521', '323', '3/2031', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-11 18:59:08', '2017-12-11 13:29:08'),
(91, 125, 1, 5, '1', '1', '90909090909090909090909090900', 'j', '090', '1/2017', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-12 12:58:56', '2017-12-12 09:49:43'),
(92, 125, 1, 5, '1', '1', '90909090909090909090909090900', 'j', '090', '1/2017', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-12 15:19:43', '2017-12-12 09:50:54'),
(93, 125, 1, 5, '1', '1', '90909090909090909090909090900', 'j', '090', '1/2017', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-12 15:20:54', '2017-12-12 09:50:54'),
(94, 130, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-13 04:46:49'),
(95, 130, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-13 04:49:13'),
(96, 130, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-15 08:44:20'),
(97, 133, 1, 6, '1', '1', '434324', 'rtertret', '444', '3/2032', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-13 15:01:55', '2017-12-13 09:31:55'),
(98, 136, 1, 6, '1', '1', '4545', '45454', '545', '10/2030', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-13 15:46:16', '2017-12-13 11:50:10'),
(99, 136, 1, 6, '1', '1', '4545', '45454', '545', '10/2030', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-13 17:20:10', '2017-12-13 12:09:49'),
(100, 136, 1, 6, '1', '1', '4545', '45454', '545', '10/2030', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-13 17:39:49', '2017-12-13 12:09:49'),
(101, 138, 1, 6, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-14 11:57:37', '2017-12-14 06:37:08'),
(102, 138, 1, 6, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-14 12:07:08', '2017-12-14 06:37:08'),
(103, 141, 1, 0, '1', '2', '', '', '', '', '2017-12-20 00:00:00', 'a', '1', '1', '1', '1', '1', 'success', '', 'inactive', '2017-12-14 15:52:18', '2017-12-14 10:32:03'),
(104, 141, 1, 0, '1', '2', '', '', '', '', '2017-12-20 00:00:00', 'a', '1', '1', '1', '1', '0', 'success', '', 'inactive', '2017-12-14 16:02:03', '2017-12-14 10:32:51'),
(105, 141, 1, 0, '1', '2', '', '', '', '', '2017-12-20 00:00:00', 'a', '1', '1', '1', '1', '0', 'success', '', 'inactive', '2017-12-14 16:02:51', '2017-12-14 10:44:12'),
(106, 141, 1, 0, '1', '2', '', '', '', '', '2017-12-20 00:00:00', 'a', '1', '1', '1', '1', '0', 'failure', '', 'inactive', '2017-12-14 16:14:12', '2017-12-14 10:46:22'),
(107, 141, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-14 10:47:49'),
(108, 141, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-14 10:48:17'),
(109, 141, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '0000-00-00 00:00:00', '2017-12-14 10:50:50'),
(110, 141, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-14 10:50:50'),
(111, 130, 2, 44, '0', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '0000-00-00 00:00:00', '2017-12-15 08:44:20'),
(112, 147, 1, 3434, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-19 11:40:06', '2017-12-19 06:10:50'),
(113, 147, 1, 3434, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'inactive', '2017-12-19 11:40:50', '2017-12-19 06:11:59'),
(114, 147, 1, 3434, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-19 11:41:59', '2017-12-19 06:11:59'),
(115, 150, 1, 3434, '1', '2', '', '', '', '', '2017-12-19 00:00:00', 'Kotak', '1000', 'Noida', '45353453', '324', '1', 'success', '', 'active', '2017-12-19 12:46:33', '2017-12-21 07:24:45'),
(116, 151, 1, 3434, '2', '0', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '1', 'success', '', 'active', '2017-12-19 18:23:03', '2017-12-19 12:53:03');

-- --------------------------------------------------------

--
-- Table structure for table `hrevents`
--

CREATE TABLE IF NOT EXISTS `hrevents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_type` int(11) NOT NULL,
  `event_category` int(11) NOT NULL,
  `keyword` varchar(100) NOT NULL,
  `organized_by` varchar(100) NOT NULL,
  `campus_name` varchar(100) NOT NULL COMMENT 'campus name as food joint name',
  `website` varchar(100) NOT NULL,
  `fee` tinyint(4) NOT NULL,
  `currency` varchar(55) NOT NULL,
  `fee_amount` float(10,2) NOT NULL,
  `discount` float(10,2) NOT NULL,
  `country` int(11) NOT NULL,
  `state` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `description` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `specialization` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `zipcode` varchar(150) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `powered_by` varchar(100) NOT NULL,
  `evntcoordinator` varchar(80) NOT NULL,
  `attachment` varchar(150) NOT NULL,
  `image` varchar(150) NOT NULL,
  `added_by` int(11) NOT NULL,
  `created_date` date NOT NULL,
  `modified_date` datetime NOT NULL,
  `posted_date` date NOT NULL,
  `status` enum('active','inactive','delete') NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `hrevents`
--

INSERT INTO `hrevents` (`id`, `user_id`, `event_name`, `event_type`, `event_category`, `keyword`, `organized_by`, `campus_name`, `website`, `fee`, `currency`, `fee_amount`, `discount`, `country`, `state`, `city`, `description`, `email`, `contact`, `specialization`, `address`, `zipcode`, `start_date`, `end_date`, `powered_by`, `evntcoordinator`, `attachment`, `image`, `added_by`, `created_date`, `modified_date`, `posted_date`, `status`, `updated_by`) VALUES
(7, 1, 'Party', 1, 14, 'dsd', 'Tekshapers', 'Food stall', 'http://projects.tekshapers.in/myfoodstall', 1, 'INR', 0.00, 0.00, 1, 2, 3, '<p>daDADAS</p>', 'shiv@yopmail.com', '3453454546545', '3', 'dasdasd', '4343434', '1970-01-01 00:00:00', '1970-01-01 00:00:00', 'aas', 'asa', 'c4d8ee6b3e84c61f5ee5d3538f88efc6.pdf', '1871f904116ced80a51f55e2eb9a1b93.jpg', 1, '2017-10-26', '2017-11-13 09:34:27', '2017-11-13', 'delete', 15),
(8, 1, 'Two mega', 3, 18, 'dds', 'abcd', 'sdDASDKJ', 'https://www.google.co.in/', 2, 'INR', 1.00, 0.00, 1, 5, 6, '<p>DSAdasd</p>', 'me@yopmail.com', '3453454546545', '', '', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'aas', 'dsads', 'd0ee40c8c842e6f9a07567cb3b4b5796.pdf', '3591f2a89018174ca182f94725e9c57d.jpg', 1, '2017-10-26', '2017-10-26 18:58:01', '2017-10-26', 'delete', 0),
(9, 1, 'Seminar', 1, 1, 'red,asda', 'Test', 'Test', '', 1, '', 0.00, 0.00, 2, 1, 1, '<p>dsf</p>', 'archana@tekshapers.com', '', '', 'gfhff', 'werewr', '2017-11-01 00:00:00', '1970-01-01 00:00:00', '', '', '8b7c174d5f11d3b77e1ffd585881fbbd.docx', '877e19702151c3013e0a010feec33555.jpg', 1, '2017-11-01', '2017-11-01 17:24:47', '2017-11-01', 'delete', 1),
(10, 1, 'VCVXCV', 1, 7, 'green', 'XCVCVC', 'CVCXVCXV', 'http://FDG', 1, '', 0.00, 0.00, 2, 1, 1, '<p>SDFDSFDSF</p>', 'FGF@GMAIL.COM', '', '', 'FDGDFG', 'VFDSGFDGD', '2017-11-09 00:00:00', '2017-11-24 00:00:00', '', '', '', '', 1, '2017-11-01', '2017-11-01 18:25:46', '2017-11-01', 'delete', 0),
(11, 1, 'ZXCZXCZX', 2, 3, '', '32423423', '42342342', 'http://234234234234', 1, '', 0.00, 0.00, 2, 1, 1, '<p>213123123123</p>', '3423@YOPMAIL.COM', '', '', '324234', '234324234', '2017-11-01 00:00:00', '2017-09-07 00:00:00', '', '', '8cb7c8668686ed1ccf3d0fe707226be8.docx', '4e8a446204bcddfee589561f0e7246e0.jpg', 1, '2017-11-01', '2017-11-01 18:26:52', '2017-11-01', 'delete', 0),
(12, 1, 'SHIV1', 1, 3, 'green', 'SADASDASDSA', 'WQEQWE', 'http://879879', 2, '', 0.00, 0.00, 2, 1, 1, '<p>FDGFDGFD</p>', '2344@YOPMAIL.COM', '', '', '234234', 'RWERWER', '2017-09-04 00:00:00', '2023-04-28 00:00:00', '', '', '', '', 1, '2017-11-01', '2017-11-01 18:31:07', '2017-11-01', 'delete', 0),
(13, 1, 'ff', 1, 14, '', 'ff', 'ff', '', 1, '', 0.00, 0.00, 2, 1, 1, '<p>ff</p>', 'baby@yopmail.com', '546436346464', '', 'dgg', '434fbg', '2017-11-01 00:00:00', '2017-11-01 00:00:00', '', '', '', '', 1, '2017-11-01', '2017-11-01 18:42:19', '2017-11-01', 'delete', 1),
(14, 1, 'fdf', 1, 0, 'green', 'dfdf', 'dfd', 'http://sfd', 1, 'INR', 0.00, 0.00, 2, 1, 1, '<p>erferr</p>', 'lovekesh1234@yopmail.com', '', '3', '12vijay vijay', '344334', '1970-01-01 00:00:00', '1970-01-01 00:00:00', '', '', '', '', 1, '2017-11-02', '2017-11-02 19:24:53', '2017-11-02', 'delete', 1),
(15, 1, 'Conference', 1, 0, 'Test,event', 'Archana', 'Burger King', 'http://xzcxzczxc', 2, 'INR', 1.00, 2.00, 1, 2, 2, '<p>sds</p>', 'archana@tekshapers.com', '34535345345', '2', 'Sec-15', '201301', '2017-11-03 00:00:00', '2017-11-06 00:00:00', 'Archana', 'Archana', 'b139c53a747c859eb0d3e455c35c98b2.docx', '83c07dcc852bf402eb3774025d6a2283.jpg', 1, '2017-11-03', '2017-11-03 14:44:52', '2017-11-03', 'delete', 1),
(16, 1, 'trtretre', 5, 0, 'jgj', 'rtretrt', 'rtret', '', 2, 'INR', 1.00, 322.00, 3, 4, 4, '<p><img alt="" src="http://projects.tekshapers.in/myfoodstall/admin/events/edit?id=6207356138"><img alt="" src="sadsadsad"><img alt="" src="http://projects.tekshapers.in/nss/uploads/cms/d4.jpg">uu</p>', '45@yopmail.com', '', '7', '45435435', '45435', '2017-12-11 00:00:00', '2019-08-01 00:00:00', '', '', '', '', 1, '2017-11-03', '2017-11-03 14:30:14', '2017-11-03', 'delete', 1),
(17, 1, 'SHIV1', 4, 0, 'red,blue', 'zfd', 'ww', 'http://http://423423432', 2, 'INR', 34.00, 23.00, 1, 5, 6, '<p>343434</p>', 'hh@yopmail.com', '342343243243243', '2', 'gfgfdg', '232323', '2017-11-06 00:00:00', '1970-01-01 00:00:00', '', '', '', '', 1, '2017-11-06', '2017-11-06 12:04:13', '2017-11-06', 'active', 0),
(18, 1, 'Education Fair', 3, 0, '', 'Tekshapers', 'Burger Point', 'http://dfdfdf', 1, 'INR', 0.00, 0.00, 1, 5, 6, '<p>Test</p>', 'archana@tekshapers.com', '', '2', 'Krishna Nagar', '110051', '2017-06-11 00:00:00', '2017-08-11 00:00:00', '', '', '', '', 1, '2017-11-06', '2017-11-06 17:49:15', '2017-11-06', 'active', 1),
(19, 1, 'Trade Fair', 3, 0, '', 'ITI', 'Food Joint', '', 2, 'INR', 500.00, 5.00, 1, 5, 6, '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.<br>\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>', 'trade@yopmail.com', '', '7', 'Pragti Maidan', '110051', '2017-11-07 00:00:00', '1970-01-01 00:00:00', '', '', '', '9391310230c5727de833b872efba6d96.jpg', 1, '2017-11-07', '2017-11-07 10:39:43', '2017-11-07', 'active', 1),
(20, 1, '898', 1, 0, '43543', '4355', '45435', 'http://454', 2, 'INR', 54545.00, 454354.00, 1, 5, 6, '<p>989</p>', 'ghghg@gmail.com', '543543545', '6', '454354', '3434', '2017-11-17 00:00:00', '2017-11-21 00:00:00', '435435', '345435', '', '', 1, '2017-11-08', '2017-11-08 12:51:30', '2017-11-08', 'active', 0),
(21, 1, 'Book Fair', 3, 0, 'book', 'aa', 'aa', 'http://sdgfdg', 2, 'INR', 500.00, 2.00, 1, 2, 2, '<p>sdada</p>', 'archana@tekshapers.com', '35345353', '2', 'Sec-18', '201301', '2017-11-09 00:00:00', '2017-11-10 00:00:00', 'aa', 'aa', '05e30c7dfb48a67f53eb68f3b4f2c89a.docx', 'ce167c7fa030ce565c088c24e50aded9.jpg', 1, '2017-11-09', '2017-11-09 13:08:40', '2017-11-09', 'active', 1),
(22, 1, 'dasda', 2, 0, 'red,kjdnf,cbkd', 'asd', 'adsadsadsad', '', 2, 'INR', 313.00, 2.00, 1, 2, 3, '<p>fsdfsdf</p>', 'ssapnaaa@yopmail.com', '543535345435435', '10', 'asdas', '342142', '2017-11-14 00:00:00', '2017-11-30 00:00:00', '', '', '', '', 1, '2017-11-13', '2017-11-14 10:46:24', '2017-11-14', 'active', 1),
(23, 1, 'ghghg', 5, 0, 'green', '78768', '687768768', 'http://projects.tekshapers.in/myfoodstall/admin/events/add', 2, 'INR', 78768.00, 768678.00, 1, 5, 6, '<p>67657</p>', '7j@yopmail.com', '67657567657', '2', '8768', '768768', '2017-11-30 00:00:00', '2017-11-30 00:00:00', '', '', '', '', 1, '2017-11-30', '2017-11-30 16:01:29', '2017-11-30', 'active', 1),
(24, 1, 'test', 2, 0, 'blue', 'himani', 'test', '', 2, 'USD', 500.00, 10.00, 1, 5, 6, '<p>gfdgfdgfdgfdg</p>', 'tanu@yopmail.com', '95915', '2', 'tyrytrr', '100563', '2017-12-04 00:00:00', '2017-12-13 00:00:00', '', '', '', '', 1, '2017-12-04', '2017-12-04 11:49:22', '2017-12-04', 'active', 1),
(25, 1, 'himani sohane', 1, 0, 'best,good,j,ghjhgjghjghjg,jghjghjghjghj,jhgjghjhgjg', '4543', 'resgula', '', 1, 'INR', 0.00, 0.00, 1, 5, 6, '<p>657657</p>', '43543@yopmail.com', '56546546', '7', 'birla collage', '110096', '2017-12-13 00:00:00', '2017-12-31 00:00:00', '7878', '78768', 'ec027691853a699cb4f16cb0f998f0d0.docx', '9e206a3cf57a24ce62508eb14f22aa1b.jpg', 1, '2017-12-13', '2017-12-19 10:58:47', '2017-12-19', 'active', 1),
(26, 1, 'ghgfh', 5, 0, 'green', 'ghdfg', 'dsfsdfdsfsd', '', 2, 'INR', 545.00, 435435.00, 1, 5, 6, '<p>fgdgdfgdfgdf</p>', 'him@yopmail.com', '09213620525', '2', 'gfdgffdg', '4543543534', '2017-12-15 00:00:00', '2017-12-15 00:00:00', 'dfdsf', '', '', 'be9fe991b2a4017349ce4182fd517112.jpg', 1, '2017-12-15', '2017-12-21 11:03:04', '2017-12-21', 'inactive', 1),
(27, 1, 'sohane', 5, 0, 'red,color,pink,green', 'rolisohane', 'roli', '', 2, 'INR', 500.00, 100.00, 1, 5, 6, '<p>dfdsfsdfsdf</p>', 'him@yopmail.com', '09213620525', '2', 'Noida city center', '110069', '2017-12-19 00:00:00', '2017-12-19 00:00:00', '', '', 'b8ecd829fd65b1bec44db80ae8b2f9d6.PDF', '', 1, '2017-12-19', '2017-12-19 11:25:06', '2017-12-19', 'active', 1),
(28, 1, 'Tekshapers Party', 3, 0, '', 'Tekshapers', 'Namo Namo', '', 1, 'INR', 0.00, 0.00, 1, 5, 6, '<p>dffgfdgfg</p>', 'namo@yopmail.com', '', '7', 'Sec-18', '201301', '2017-12-19 00:00:00', '2017-12-23 00:00:00', '', '', '60711776c23b4c7d76211926d47879da.docx', '', 1, '2017-12-19', '2017-12-19 16:06:28', '2017-12-19', 'active', 1);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
