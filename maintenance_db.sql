-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 09, 2025 at 01:28 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maintenance_db`
--
CREATE DATABASE IF NOT EXISTS `maintenance_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `maintenance_db`;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `Category_ID` varchar(100) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Category_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`Category_ID`, `name`) VALUES
('C001', 'Electrical'),
('C002', 'Plumbing'),
('C003', 'HVAC'),
('C004', 'Building/Infrastructure'),
('C005', 'Equipment/Machinery'),
('C006', 'IT/Network'),
('C007', 'Safety & Security'),
('C008', 'Cleaning/Janitorial'),
('C009', 'Furniture/Fixtures'),
('C010', 'Grounds/Landscaping');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `Comment_ID` int NOT NULL AUTO_INCREMENT,
  `Report_ID` varchar(10) NOT NULL,
  `User_ID` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Comment_ID`),
  KEY `Report_ID` (`Report_ID`),
  KEY `User_ID` (`User_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
CREATE TABLE IF NOT EXISTS `media` (
  `Media_ID` int NOT NULL AUTO_INCREMENT,
  `file_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_time` datetime NOT NULL,
  `Report_ID` varchar(4) NOT NULL,
  `uploaded_by` int NOT NULL,
  PRIMARY KEY (`Media_ID`),
  KEY `Report_ID` (`Report_ID`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

DROP TABLE IF EXISTS `report`;
CREATE TABLE IF NOT EXISTS `report` (
  `Report_ID` varchar(4) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `report_date` date NOT NULL,
  `User_ID` int DEFAULT NULL,
  `Category_ID` int DEFAULT NULL,
  `Urgency_ID` int DEFAULT NULL,
  PRIMARY KEY (`Report_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `Category_ID` (`Category_ID`),
  KEY `Urgency_ID` (`Urgency_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status_log`
--

DROP TABLE IF EXISTS `status_log`;
CREATE TABLE IF NOT EXISTS `status_log` (
  `Status_ID` int NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_time` timestamp NULL DEFAULT NULL,
  `notes` text,
  `Report_ID` int DEFAULT NULL,
  PRIMARY KEY (`Status_ID`),
  KEY `Report_ID` (`Report_ID`),
  KEY `updated_by` (`updated_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `urgency_level`
--

DROP TABLE IF EXISTS `urgency_level`;
CREATE TABLE IF NOT EXISTS `urgency_level` (
  `Urgency_ID` varchar(4) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Urgency_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `urgency_level`
--

INSERT INTO `urgency_level` (`Urgency_ID`, `label`) VALUES
('U001', 'Low'),
('U002', 'Medium'),
('U003', 'High');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `User_ID` varchar(4) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Staff','Manager','Technician') NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`User_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `name`, `email`, `role`, `password`, `profile_picture`) VALUES
('0', 'Maisara Siput', 'rayn2309@gmail.com', 'Staff', '$2y$10$vjoY/wWFOQ58XIgKOO09GO0MQVRKAYTk52NkbcmflFFQqLblPSXNu', 'profile_0.png'),
('', 'Jane', 'jane10@gmail.com', 'Staff', '$2y$10$kpAx7.5OUX4DBoaTvyaz0Owj/TV2QqY.mqjRIfThVHmpFHxjMsUt.', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
