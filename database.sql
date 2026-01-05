-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 11:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `booking_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `total_price` decimal(12,0) NOT NULL DEFAULT 0,
  `booked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `service_id`, `start_time`, `end_time`, `status`, `total_price`, `booked_at`, `payment_status`) VALUES
(1, 9, 1, '2025-11-21 10:00:00', '2025-11-21 11:00:00', 'cancelled', 0, '2025-11-21 08:21:25', 0),
(2, 11, 1, '2025-11-21 20:00:00', '2025-11-21 21:00:00', 'cancelled', 0, '2025-11-21 09:54:28', 0),
(3, 11, 1, '2025-11-21 20:00:00', '2025-11-21 21:00:00', 'cancelled', 0, '2025-11-21 11:55:51', 0),
(4, 9, 1, '2025-11-22 20:00:00', '2025-11-22 21:00:00', 'cancelled', 0, '2025-11-22 06:04:30', 0),
(5, 11, 6, '2025-11-23 16:00:00', '2025-11-23 17:00:00', 'confirmed', 0, '2025-11-22 16:22:06', 0),
(6, 15, 3, '2025-11-23 10:00:00', '2025-11-23 12:00:00', 'confirmed', 0, '2025-11-22 17:05:50', 0),
(7, 16, 2, '2025-12-02 09:00:00', '2025-12-02 10:00:00', 'confirmed', 0, '2025-12-02 06:36:45', 0),
(8, 9, 1, '2026-01-04 20:00:00', '2026-01-04 21:00:00', 'confirmed', 0, '2026-01-04 02:52:38', 1),
(9, 9, 3, '2026-01-04 18:00:00', '2026-01-04 20:00:00', 'confirmed', 0, '2026-01-04 03:20:28', 0),
(10, 9, 1, '2026-01-04 18:00:00', '2026-01-04 19:00:00', 'confirmed', 0, '2026-01-04 03:24:29', 1),
(11, 9, 1, '2026-01-06 18:00:00', '2026-01-06 21:00:00', 'confirmed', 0, '2026-01-04 04:17:46', 0),
(12, 9, 2, '2026-01-04 15:00:00', '2026-01-04 20:00:00', 'confirmed', 750000, '2026-01-04 04:32:23', 0),
(13, 9, 1, '2026-02-10 10:00:00', '2026-02-10 13:00:00', 'cancelled', 0, '2026-01-04 04:37:46', 0),
(14, 9, 1, '2026-01-05 12:00:00', '2026-01-05 15:00:00', 'cancelled', 240000, '2026-01-04 04:41:31', 0),
(15, 9, 2, '2026-01-05 12:00:00', '2026-01-05 15:00:00', 'confirmed', 450000, '2026-01-04 04:42:24', 1),
(16, 9, 3, '2026-01-30 14:00:00', '2026-01-30 15:00:00', 'pending', 200000, '2026-01-04 05:41:40', 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `code`, `image`) VALUES
(1, 'Phòng Học / Phòng Họp', 'study', 'cat_study.jpg'),
(2, 'Phòng Sức Khỏe', 'health', 'cat_health.jpg'),
(3, 'Phòng Thể Dục (Gym/Yoga)', 'fitness', 'cat_fitness.jpg'),
(4, 'Phòng Chụp Hình', 'photo', 'cat_photo.jpg'),
(5, 'Phòng Tổ Chức Tiệc', 'party', 'cat_party.jpg'),
(11, 'Phoòng game', 'game', '');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `capacity`) VALUES
(1, 'Small Room (Nhỏ)', 10),
(2, 'Big Room (Lớn)', 50),
(3, 'Cá Nhân (Private)', 1),
(4, 'Tập Thể (Public)', 20);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `min_hours` int(11) DEFAULT 1,
  `max_hours` int(11) DEFAULT 24,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `room_type_id`, `name`, `description`, `price`, `duration_minutes`, `min_hours`, `max_hours`, `image`, `is_active`) VALUES
(1, 1, 1, 'Phòng Học Nhỏ', NULL, 80000.00, 60, 2, 6, 'small1_1.jpg', 1),
(2, 1, 2, 'Phòng Họp Lớn', NULL, 150000.00, 60, 2, 6, 'small1_2.jpg', 1),
(3, 2, 3, 'Xông Hơi Thư Giãn', NULL, 200000.00, 120, 2, 2, 'xonghoichinh.jpg', 1),
(4, 2, 3, 'Hồi Sức Tỉnh Táo', NULL, 400000.00, 120, 2, 2, 'phonghoisuc.jpg', 1),
(5, 3, 4, 'Gym - Tập Thể', NULL, 100000.00, 60, 1, 24, 'gymtapthe.jpg', 1),
(6, 3, 3, 'Gym - Cá Nhân (PT)', NULL, 300000.00, 60, 1, 24, 'gymcanhan.jpg', 1),
(7, 3, 4, 'Yoga - Tập Thể', NULL, 100000.00, 60, 1, 24, 'yogatapthe.jpg', 1),
(8, 4, NULL, 'Chụp Chân Dung', NULL, 500000.00, 60, 1, 3, 'anhchandung.jpg', 1),
(9, 4, NULL, 'Phòng Vô Cực', NULL, 500000.00, 60, 1, 3, 'phongvocuc.jpg', 1),
(10, 5, 1, 'Tiệc Sinh Nhật Nhỏ', NULL, 500000.00, 60, 2, 4, 'sinhnhatnho.jpg', 1),
(11, 5, 2, 'Tiệc Liên Hoan Lớn', NULL, 800000.00, 60, 2, 4, 'lienhoanlon.jpg', 1),
(12, 3, 3, 'Yoga cá nhân (Có PT)', '', 300000.00, 60, 1, 24, 'yogacanhan.jpg', 1),
(16, 11, 4, 'game VIP', '', 35000.00, 60, 1, 24, '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('client','admin') NOT NULL DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `role`, `created_at`) VALUES
(2, 'nguyenthuylua123', '$2y$10$KW6WZ3L2yfAPVXtAjZN1yeJjkPWM57ecpbidDu.Jq0mA4nIZZ5Udi', '', NULL, NULL, '', '2025-10-24 16:53:06'),
(9, 'dh52201162', '$2y$10$2vDmuO1m2PzjrHyA2hKMLeBYnJPnqI5km4uqlv5s77GqlZSbhLT5C', 'dh52201162@student.stu.edu.vn', NULL, NULL, 'admin', '2025-10-24 17:45:22'),
(10, 'thuylua1702', '$2y$10$zz7KxjwEGj5I4jc6D2xZ2eQdHlrA/TZX3z0uk/0t48/iaF6I0BxEq', 'thuylua1702@gmail.com', NULL, NULL, '', '2025-10-24 17:55:08'),
(11, '123nhu', '$2y$10$244dHE7F11Fl9BEAYirKSOeeuqWtwjfGddgLnsiy2LiaMW7ousc3O', 'nhu123@gmail.com', NULL, NULL, '', '2025-10-31 12:52:45'),
(12, 'congdanh123', '$2y$10$TxBj7vZBMzCSJFFNQWSBWuEAuX0q2nTNhCFLHtG/FDFN4tQTt3wtO', 'adbc@gmail.com', NULL, NULL, '', '2025-11-01 08:58:16'),
(13, 'trungcong', '$2y$10$3cvj/cNuFl.VcEZO79ROU.00/cQNWD7a2V057dczLO.0lFG3a4HMa', 'trungcong@gmail.com', NULL, NULL, '', '2025-11-21 06:46:24'),
(14, 'lua1702', '$2y$10$o2PaE5Ts6vy5VVXpqPLAAeRAqTdMn3NpPhX4lgYnQWhPjkHZ34cKO', 'lua1702@gmail.com', NULL, NULL, '', '2025-11-22 16:21:05'),
(15, 'mui123', '$2y$10$crS3KaNE9Umvz09GuV0oReo2qrbdl887ScDzjttTKXcp2DkbvRbAO', 'mui123@gmail.com', NULL, NULL, '', '2025-11-22 17:05:17'),
(16, 'khanhduy123', '$2y$10$yaO2ehZ.iuu8ThjhX6njVur6m/S0HGA1SMJwN49giHYEiZFNthuBy', 'khanhduy123@gmail.com', NULL, NULL, '', '2025-12-02 06:36:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
