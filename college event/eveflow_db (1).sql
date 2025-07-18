-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 06:17 PM
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
-- Database: `eveflow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminid` int(11) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(50) NOT NULL,
  `last_login` datetime DEFAULT NULL COMMENT 'Timestamp of last successful login'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminid`, `email`, `password`, `last_login`) VALUES
(1, 'admin@gmail.com', '123', '2025-05-10 14:09:58');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `event_type` enum('Individual','Team') NOT NULL,
  `status` enum('Pending','Ongoing','Completed') NOT NULL DEFAULT 'Pending',
  `organizer_id` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_date`, `venue_id`, `event_type`, `status`, `organizer_id`, `created_at`) VALUES
(4, 'workshop', '2025-05-23', NULL, 'Individual', 'Pending', 'org-1', '2025-05-15 15:19:52'),
(5, 'cricket', '2025-05-15', NULL, 'Team', 'Ongoing', 'org-1', '2025-05-15 15:32:51');

-- --------------------------------------------------------

--
-- Table structure for table `organizers`
--

CREATE TABLE `organizers` (
  `organizer_id` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `organizer_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(255) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `organizers`
--

INSERT INTO `organizers` (`organizer_id`, `email`, `organizer_name`, `phone`, `department`, `semester`, `role`, `password`, `created_at`, `updated_at`) VALUES
('org-1', 'p@gmail.com', 'Pratham Debnath', '9101333411', 'Computer Science', '6', 'ORGANIZER', 'Prem@2025', '2025-04-11 01:48:27', '2025-04-11 01:48:27'),
('org-2', 'o@gmail.com', 'Sammy', '8124359275', 'Geography', '6', 'organizer', '1234567@S', '2025-05-06 08:04:12', '2025-05-06 08:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `participations`
--

CREATE TABLE `participations` (
  `pt_id` int(11) NOT NULL,
  `p_id` varchar(20) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `participation_type` varchar(50) NOT NULL,
  `team_name` varchar(255) DEFAULT NULL,
  `team_members` text DEFAULT NULL,
  `result` varchar(20) NOT NULL DEFAULT 'pending',
  `certificate_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `participations`
--

INSERT INTO `participations` (`pt_id`, `p_id`, `st_id`, `event_id`, `student_name`, `event_name`, `participation_type`, `team_name`, `team_members`, `result`, `certificate_path`, `created_at`) VALUES
(10, 'prt-std-1', 'std-1', 4, 'Prashanjeet Dutta', 'workshop', 'individual', '', '', 'pending', NULL, '2025-05-15 15:32:10');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `sid` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`sid`, `department`, `course`) VALUES
(1, 'Anthropology', 'Bachelor of Arts'),
(2, 'Assamese', 'Bachelor of Arts'),
(3, 'Economics', 'Bachelor of Arts'),
(4, 'English', 'Bachelor of Arts'),
(5, 'Geography', 'Bachelor of Arts'),
(6, 'History', 'Bachelor of Arts'),
(7, 'Mathematics', 'Bachelor of Arts'),
(8, 'Political Science', 'Bachelor of Arts'),
(9, 'Philosophy', 'Bachelor of Arts'),
(10, 'Sanskrit', 'Bachelor of Arts'),
(11, 'Statistics', 'Bachelor of Arts'),
(12, 'Physics', 'Bachelor of Science'),
(13, 'Chemistry', 'Bachelor of Science'),
(14, 'Mathematics', 'Bachelor of Science'),
(15, 'Botany', 'Bachelor of Science'),
(16, 'Zoology', 'Bachelor of Science'),
(17, 'Anthropology', 'Bachelor of Science'),
(18, 'Statistics', 'Bachelor of Science'),
(19, 'Economics', 'Bachelor of Science'),
(20, 'Geography', 'Bachelor of Science'),
(21, 'Computer Science', 'Bachelor of Science'),
(22, 'Computer Science', 'Bachelor of Computer Application'),
(23, 'English', 'M.A. in English Literature'),
(24, 'Mathematics', 'M.Sc. in Mathematics'),
(25, 'Anthropology', 'M.Sc. in Anthropology');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `st_id` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`st_id`, `email`, `student_name`, `course`, `dept`, `semester`, `password`, `created_at`) VALUES
('std-1', 'student2@gmail.com', 'Prashanjeet Dutta', 'Bachelor of Computer Application', 'Computer Science', '6', 'Stu@2025', '2025-04-08 18:41:18'),
('std-2', 'student3@gmail.com', 'test', 'Bachelor of Science', 'Anthropology', '3', 'Test@123', '2025-04-08 22:29:05'),
('std-3', 'st1@gmail.com', 'st', 'Bachelor of Science', 'Anthropology', '3', 'Stu@1234', '2025-04-08 23:04:56'),
('std-4', 'st2@gmail.com', 'stst', 'Bachelor of Arts', 'Assamese', '3', 'Stu2@234', '2025-04-08 23:05:58'),
('std-5', 'premdebnath08@gmail.com', 'Pratham Debnath', 'Bachelor of Computer Application', 'Computer Science', '6', 'Prem123456@', '2025-04-10 03:48:35'),
('std-6', 's@gmail.com', 'SURESH', 'Bachelor of Arts', 'History', '6', '123456P@', '2025-04-11 01:52:26'),
('std-7', 'q@gmail.com', 'SURESHIIII', 'Bachelor of Arts', 'English', '6', '123456P@', '2025-04-11 01:55:57');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `venue_id` int(11) NOT NULL,
  `venue_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `venue_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`venue_id`, `venue_name`, `location`, `venue_type`) VALUES
(1, 'Sunset Hall', 'Los Angeles', 'Indoor'),
(2, 'Oceanview Garden', 'Miami', 'Outdoor'),
(3, 'Starlight Arena', 'New York', 'Stadium'),
(4, 'Royal Palace', 'Chicago', 'Hotel'),
(5, 'The Grand Gallery', 'San Francisco', 'Exhibition');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `organizers`
--
ALTER TABLE `organizers`
  ADD PRIMARY KEY (`organizer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `participations`
--
ALTER TABLE `participations`
  ADD PRIMARY KEY (`pt_id`),
  ADD UNIQUE KEY `unique_participation` (`st_id`,`event_id`),
  ADD KEY `p_id` (`p_id`),
  ADD KEY `st_id` (`st_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`st_id`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `participations`
--
ALTER TABLE `participations`
  MODIFY `pt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venue` (`venue_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`organizer_id`) REFERENCES `organizers` (`organizer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `participations`
--
ALTER TABLE `participations`
  ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`st_id`) REFERENCES `users` (`st_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
