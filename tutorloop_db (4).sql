-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2026 at 03:19 PM
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
-- Database: `tutorloop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `feedback_ratings`
--

CREATE TABLE `feedback_ratings` (
  `feedback_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `tutor_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `feedback_comment` text DEFAULT NULL
) ;

--
-- Dumping data for table `feedback_ratings`
--

INSERT INTO `feedback_ratings` (`feedback_id`, `session_id`, `tutor_id`, `rating`, `feedback_comment`) VALUES
(1, 2, 14, 5, '');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message_content` text DEFAULT NULL,
  `date_sent` datetime DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message_content`, `date_sent`, `is_read`) VALUES
(1, 13, 14, 'hello', '2026-04-23 07:10:14', 1),
(2, 15, 11, 'hi good morning', '2026-04-23 10:13:33', 0),
(3, 15, 14, 'hello', '2026-04-23 10:14:26', 1),
(4, 14, 15, 'hi good morning', '2026-04-23 10:14:47', 0),
(5, 15, 16, 'yea', '2026-04-23 10:43:41', 0),
(6, 16, 15, 'okay', '2026-04-23 10:46:37', 0),
(7, 18, 17, 'hello! Are you available tomorrow?', '2026-04-23 19:45:42', 0),
(8, 17, 18, 'hello! yes.', '2026-04-23 19:46:57', 0),
(9, 19, 20, 'Hello!', '2026-04-23 20:13:37', 0),
(10, 20, 19, 'hi!', '2026-04-23 20:14:53', 0),
(11, 14, 13, 'gfggu', '2026-05-27 17:11:16', 1),
(12, 14, 13, 'yueyhwjsjhfbd', '2026-05-27 17:11:41', 1),
(13, 13, 14, 'helloo hahjhhduahu', '2026-05-27 17:23:17', 1),
(14, 14, 13, 'hgg', '2026-05-27 17:30:47', 1),
(15, 14, 13, 'a', '2026-05-27 17:30:53', 1),
(16, 14, 13, 'b', '2026-05-27 17:30:59', 1),
(17, 14, 13, 'c', '2026-05-27 17:31:02', 1),
(18, 14, 13, 'd', '2026-05-27 17:31:05', 1),
(19, 14, 13, 'e', '2026-05-27 17:31:08', 1),
(20, 14, 13, 'gghjsaruiyhrh gfusyurg', '2026-05-27 17:31:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `tutor_id` int(11) DEFAULT NULL,
  `tutee_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `requested_schedule` datetime DEFAULT NULL,
  `request_note` text DEFAULT NULL,
  `session_status` enum('Pending','Accepted','Declined','Completed','Expired') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `tutor_id`, `tutee_id`, `subject_id`, `requested_schedule`, `request_note`, `session_status`) VALUES
(1, 9, 8, 1, '2026-03-30 15:00:00', 'Please accept my request. Thank you!', ''),
(2, 14, 13, 5, '2026-04-24 13:00:00', '', 'Completed'),
(3, 14, 15, 5, '2026-04-24 11:00:00', 'are you available tomorrow friday sir?', 'Completed'),
(4, 16, 15, 6, '2026-04-24 13:01:00', 'please respond asap', 'Accepted'),
(5, 17, 18, 7, '2026-04-24 14:00:00', '', 'Completed'),
(6, 20, 19, 1, '2026-04-27 08:30:00', 'Hello! ', 'Completed'),
(7, 9, 13, NULL, '2026-05-27 19:45:00', '', 'Pending'),
(8, 14, 13, 5, '2026-05-27 09:45:00', '', 'Pending'),
(9, 14, 13, 5, '2026-06-01 13:47:00', '', 'Pending'),
(10, 14, 13, 5, '2026-06-01 15:47:00', '', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`) VALUES
(1, 'Programming'),
(2, 'Math'),
(3, 'Science'),
(4, 'Calculus'),
(5, 'English'),
(6, 'History'),
(7, 'Application Development');

-- --------------------------------------------------------

--
-- Table structure for table `tutor_availability`
--

CREATE TABLE `tutor_availability` (
  `id` int(11) NOT NULL,
  `tutor_subject_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutor_availability`
--

INSERT INTO `tutor_availability` (`id`, `tutor_subject_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(17, 21, 'Wednesday', '09:00:00', '12:00:00'),
(18, 21, 'Monday', '13:00:00', '17:00:00'),
(19, 22, 'Monday', '16:37:00', '20:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `tutor_profiles`
--

CREATE TABLE `tutor_profiles` (
  `tutor_profiles_id` int(11) NOT NULL,
  `tutor_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `tutoring_rate` decimal(10,2) DEFAULT NULL,
  `average_rating` decimal(4,2) DEFAULT 0.00,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutor_profiles`
--

INSERT INTO `tutor_profiles` (`tutor_profiles_id`, `tutor_id`, `description`, `phone_number`, `tutoring_rate`, `average_rating`, `subject_id`) VALUES
(2, 11, 'I specialize in Math and Algebra for beginners.', NULL, 200.00, 5.00, 2),
(3, 9, 'A bachelor of science in computer science student in isatu who specializes in programming', '09876543210', 100.00, 0.00, 1),
(4, 14, 'A working student.', '09457838657', 200.00, 5.00, 5),
(5, 16, 'A student at ISATU', '09939775972', 100.00, NULL, 6),
(6, 17, 'A Bachelor of Science in Computer Science student.', '09876543210', 150.00, NULL, 7),
(7, 20, 'A student at ISATU.', '09876543210', 200.00, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tutor_subjects`
--

CREATE TABLE `tutor_subjects` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutor_subjects`
--

INSERT INTO `tutor_subjects` (`id`, `tutor_id`, `subject_id`, `rate`) VALUES
(21, 14, 5, 400.00),
(22, 14, 4, 300.00),
(23, 14, 7, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('tutor','tutee') DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `student_id`, `profile_pic`, `is_verified`) VALUES
(8, 'Demo Tutee', 'demotutee@gmail.com', '$2y$10$kOxQp3Xm/.RnEPoAQEJk2uN/sHI6HrfJhWtuBAw99WxZDfiwzraCG', 'tutee', '2026-ABCD', '1776862900_tutee_pic.jpg', 1),
(9, 'Demo Tutor', 'demotutor@gmail.com', '$2y$10$rQNO8DBQgGFe/Fq0cg1wAO/WG9EjsazzOCSqi9S7YBY2x3YKYnCpu', 'tutor', '2026-EFGH', 'tutor_9_1776866524.jpg', 1),
(10, 'Eunesse', 'eunesse@gmail.com', '$2y$10$/5sZ.iJ.ZHQvsFSTiRFCa.FZ0nCLsQbf2IB9cdeQeEf7Z8D1pbi2u', 'tutor', '2026-EFCD', NULL, 1),
(11, 'John Tutor', 'tutor@test.com', 'tutortest', 'tutor', '1993-123', NULL, 1),
(12, 'Jane Student', 'student@test.com', 'tuteetest', 'tutee', '1993-129', NULL, 1),
(13, 'Demo Tutee', 'demotutee@students.isatu.edu.ph', '$2y$10$ELcpyHOVgg45g/QebHuAyOCHpqWEfrIlRgpCpTOu2QtNiOsNnE2Rm', 'tutee', '2026-2674-A', '1776870176_tutee_pic.jpg', 1),
(14, 'Demo Tutor', 'demotutor@students.isatu.edu.ph', '$2y$10$4fFptOA0LVxghaH8xlmn2.kS8C3c0unAQfKczxCKmE2o1j5Pq7l8C', 'tutor', '2026-1234-B', 'tutor_14_1776870340.jpg', 1),
(15, 'Sharyn May Allonar', 'sharynmay.allonar@students.isatu.edu.ph', '$2y$10$XZ5SvrofokYabSqLNvcmhOWdAMa6sRHDmGwuJJene4bP8H99X3C8W', 'tutee', '2024-0996-A', '1776911213_download (9).jpg', 1),
(16, 'Mary Claire Jordan', 'maryclaire.jordan@students.isatu.edu.ph', '$2y$10$gSGCkM1EB8fXUNiC/gLTyuAaUI/ePjrhrmLlrJIA9lR7KNTQVXPgq', 'tutor', '2024-2476-I', 'tutor_16_1776911950.jpg', 1),
(17, 'Mary Claire Jordan', 'maryclaire.pjordan@students.isatu.edu.ph', '$2y$10$FYMMo89gSxLlyM0yjDVw3e.BB3KeSAIJkqU8Nd5A/pofoESTus.sq', 'tutor', '2024-2429-I', 'tutor_17_1776944448.jpg', 1),
(18, 'Sharyn May Allonar', 'sharynmay.aallonar@students.isatu.edu.ph', '$2y$10$mkVsOn6cH/w5XkkI6di.w.O3J/9/hmZz6duq15yjnh5E6IHhIYuLm', 'tutee', '2024-2004-A', '1776944605_tutee_pic.jpg', 1),
(19, 'Tutee Test', 'tuteetest@students.isatu.edu.ph', '$2y$10$Bu7A8WhOi9jVCiP5evQBh.1TjKdk0Ooo0DZvh4Y8nrlkY.0zGCpDu', 'tutee', '2024-2476-A', '1776946028_tutee_pic.jpg', 1),
(20, 'Tutor Test', 'tutortest@students.isatu.edu.ph', '$2y$10$ThLjEgT3dqFZvKRV4eJo6uqeP.C2CApZaTaD1BkLtElI5lpKr3nve', 'tutor', '2025-2026-A', 'tutor_20_1776946239.jpg', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `feedback_ratings`
--
ALTER TABLE `feedback_ratings`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `tutee_id` (`tutee_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `tutor_availability`
--
ALTER TABLE `tutor_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_subject_id` (`tutor_subject_id`);

--
-- Indexes for table `tutor_profiles`
--
ALTER TABLE `tutor_profiles`
  ADD PRIMARY KEY (`tutor_profiles_id`),
  ADD UNIQUE KEY `tutor_id` (`tutor_id`),
  ADD KEY `fk_tutor_subject` (`subject_id`);

--
-- Indexes for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `feedback_ratings`
--
ALTER TABLE `feedback_ratings`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tutor_availability`
--
ALTER TABLE `tutor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tutor_profiles`
--
ALTER TABLE `tutor_profiles`
  MODIFY `tutor_profiles_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback_ratings`
--
ALTER TABLE `feedback_ratings`
  ADD CONSTRAINT `feedback_ratings_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `feedback_ratings_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`tutee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `sessions_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`);

--
-- Constraints for table `tutor_availability`
--
ALTER TABLE `tutor_availability`
  ADD CONSTRAINT `tutor_availability_ibfk_1` FOREIGN KEY (`tutor_subject_id`) REFERENCES `tutor_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutor_profiles`
--
ALTER TABLE `tutor_profiles`
  ADD CONSTRAINT `fk_tutor_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `tutor_profiles_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  ADD CONSTRAINT `tutor_subjects_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutor_profiles` (`tutor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tutor_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
