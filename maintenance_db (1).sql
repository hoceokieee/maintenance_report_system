-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 03:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `Category_ID` varchar(100) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `comments` (
  `Comment_ID` int(11) NOT NULL,
  `Report_ID` varchar(10) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `Media_ID` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_time` datetime NOT NULL,
  `Report_ID` varchar(4) NOT NULL,
  `uploaded_by` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`Media_ID`, `file_type`, `file_path`, `upload_time`, `Report_ID`, `uploaded_by`) VALUES
(4, 'image/png', '2025/07/report_686512bc2607e_20250702_130636.png', '2025-07-02 19:06:36', 'R002', 3),
(5, 'image/jpeg', '2025/07/report_6865175cc82b5_20250702_132620.jpg', '2025-07-02 19:26:20', 'R001', 3),
(6, 'image/jpeg', '2025/07/report_686517beea6cd_20250702_132758.jpeg', '2025-07-02 19:27:58', 'R002', 3);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `Report_ID` varchar(4) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `report_date` date NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Category_ID` varchar(4) DEFAULT NULL,
  `Urgency_ID` varchar(4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`Report_ID`, `title`, `description`, `location`, `report_date`, `User_ID`, `Category_ID`, `Urgency_ID`) VALUES
('R002', 'PC tak boleh on', 'PC #21', 'MPD1', '2025-07-02', 3, 'C006', 'U002'),
('R001', 'Tandas Rosak', 'Tandas tersumbat dan paip tercabut', 'Toliet perempuan aras 2', '2025-07-02', 3, 'C002', 'U003');

-- --------------------------------------------------------

--
-- Table structure for table `status_log`
--

CREATE TABLE `status_log` (
  `Status_ID` int(11) NOT NULL,
  `status` varchar(244) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_time` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `Report_ID` varchar(4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_log`
--

INSERT INTO `status_log` (`Status_ID`, `status`, `updated_by`, `updated_time`, `notes`, `Report_ID`) VALUES
(9, 'Pending', 3, '2025-07-02 11:27:58', 'Initial submission', 'R002'),
(8, 'In Progress', 5, '2025-07-02 11:26:36', NULL, 'R001'),
(7, 'Pending', 3, '2025-07-02 11:26:20', 'Initial submission', 'R001'),
(19, 'Completed', 5, '2025-07-02 12:23:03', NULL, 'R001'),
(15, 'In Progress', 5, '2025-07-02 12:14:07', NULL, 'R002');

-- --------------------------------------------------------

--
-- Table structure for table `urgency_level`
--

CREATE TABLE `urgency_level` (
  `Urgency_ID` varchar(4) NOT NULL,
  `label` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `users` (
  `User_ID` int(4) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Staff','Manager','Technician','customer') NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `name`, `email`, `role`, `password`, `profile_picture`) VALUES
(2, 'Maisara Siput', 'rayn2309@gmail.com', 'Staff', '$2y$10$vjoY/wWFOQ58XIgKOO09GO0MQVRKAYTk52NkbcmflFFQqLblPSXNu', 'profile_0.png'),
(1, 'Harlina', 'harlina10@gmail.com', 'customer', '$2y$10$30hyN1NrIrU8pemT.cojFOXkegFOGc9wBFnmQy41lbDHuoPr/fo4u', NULL),
(3, 'Azalina', 'azalina@gmail.com', 'customer', '$2y$10$MiKIiCiZREJs38jfLFor9O0i2l2uQoeDNv7a45Dte3is.0plPO/kK', NULL),
(4, 'evan', 'evan@gmail.com', 'Admin', '$2y$10$0b5dl8a2eWy44yrl/t/HfeUrmScYPyS5QHD6AgG2eVYWSR7wfMfb.', NULL),
(5, 'Harsya', 'harsya@gmail.com', 'Staff', '$2y$10$JDgNEK39lCUIUFP0/wzPnue7lmlXKW5LZHYR.dIQG1XkdizG9ifum', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`Category_ID`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`Comment_ID`),
  ADD KEY `Report_ID` (`Report_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`Media_ID`),
  ADD KEY `Report_ID` (`Report_ID`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Category_ID` (`Category_ID`),
  ADD KEY `Urgency_ID` (`Urgency_ID`);

--
-- Indexes for table `status_log`
--
ALTER TABLE `status_log`
  ADD PRIMARY KEY (`Status_ID`),
  ADD KEY `Report_ID` (`Report_ID`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `urgency_level`
--
ALTER TABLE `urgency_level`
  ADD PRIMARY KEY (`Urgency_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `Comment_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `Media_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_log`
--
ALTER TABLE `status_log`
  MODIFY `Status_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
