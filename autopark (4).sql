-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2025 at 11:11 AM
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
-- Database: `autopark`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vehicle_no` varchar(50) NOT NULL,
  `vehicle_type` enum('2W','4W') NOT NULL,
  `ev` enum('yes','no') DEFAULT 'no',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `membership` enum('none','7days','15days','30days') DEFAULT 'none',
  `price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `status` enum('active','expired','cancelled','completed') NOT NULL,
  `booked_by` enum('ONLINE','OFFLINE') DEFAULT 'ONLINE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `slot_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `parking_id`, `user_id`, `vehicle_no`, `vehicle_type`, `ev`, `start_time`, `end_time`, `membership`, `price`, `payment_method`, `status`, `booked_by`, `created_at`, `slot_number`) VALUES
(60, 4, 0, 'MH07N1986', '4W', 'yes', '2025-09-01 12:05:00', '2025-09-01 14:05:00', '', 140.00, 'Cash', '', 'ONLINE', '2025-09-01 05:54:43', 'F1-4W-1'),
(61, 4, 0, 'MH07N1986', '4W', 'no', '2025-09-01 11:35:00', '2025-09-30 11:35:00', '', 41760.00, 'Cash', '', 'ONLINE', '2025-09-01 06:05:50', 'F1-4W-2'),
(62, 4, 0, 'MH07N1986', '2W', 'yes', '2025-09-01 13:45:00', '2025-09-01 15:45:00', '', 120.00, 'Cash', 'active', 'ONLINE', '2025-09-01 07:14:41', 'F1-2W-1');

-- --------------------------------------------------------

--
-- Table structure for table `floor_status`
--

CREATE TABLE `floor_status` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `floor_status`
--

INSERT INTO `floor_status` (`id`, `owner_id`, `floor_number`, `status`) VALUES
(1, 4, 1, 'open'),
(2, 8, 1, 'open'),
(3, 3, 1, 'open'),
(4, 3, 2, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `sender_role` enum('user','owner') DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parkings`
--
-- Error reading structure for table autopark.parkings: #1932 - Table &#039;autopark.parkings&#039; doesn&#039;t exist in engine
-- Error reading data for table autopark.parkings: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `autopark`.`parkings`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `parking_requests`
--

CREATE TABLE `parking_requests` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `owner_email` varchar(255) DEFAULT NULL,
  `parking_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `ev_support` enum('yes','no') DEFAULT 'no',
  `supported_vehicles` text DEFAULT NULL,
  `slot_2w` int(11) DEFAULT 0,
  `slot_4w` int(11) DEFAULT 0,
  `status` enum('waiting','approved','rejected') DEFAULT 'waiting',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `floors` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_requests`
--

INSERT INTO `parking_requests` (`id`, `owner_id`, `owner_email`, `parking_name`, `address`, `mobile`, `latitude`, `longitude`, `ev_support`, `supported_vehicles`, `slot_2w`, `slot_4w`, `status`, `requested_at`, `location`, `created_at`, `floors`) VALUES
(4, 4, 'adigawade2006@gmail.com', 'Aadi Parking ', 'Mochemad,holkarWadi,tank,sindhudurg', '8975347452', 15.90231040, 73.81975040, 'yes', 'Truck,Car,Bike', 10, 20, 'approved', '2025-08-05 16:53:45', '', '2025-08-07 15:05:46', 2),
(23, 3, 'preetipednekar2006@gmail.com', 'preeti parking', 'sawantwadi spk collage', '55151132', 15.90296250, 73.82107070, 'yes', 'Truck,Car,Bike', 20, 21, 'approved', '2025-08-13 10:16:29', '', '2025-08-13 10:16:29', 2);

-- --------------------------------------------------------

--
-- Table structure for table `parking_slots`
--

CREATE TABLE `parking_slots` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `slot_number` varchar(50) NOT NULL,
  `slot_type` enum('2W','4W') NOT NULL,
  `status` enum('available','occupied') NOT NULL DEFAULT 'available',
  `occupied_until` datetime DEFAULT NULL,
  `current_booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `penalty_amount` decimal(10,2) NOT NULL,
  `refund_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','owner','admin') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(3, 'Preeti Vinod Pednekar', 'preetipednekar2006@gmail.com', '$2y$10$Oyyob3REDSDchTGisWpWz.IKEMOZBriib4jq9t2hcB6WToz0Bea6S', 'owner', '2025-08-04 14:44:17'),
(4, 'Narayan Ashok Gawade', 'adigawade2006@gmail.com', '$2y$10$hgIv60yDIceykSOsVapdH.sO.6amUeMLMO0hiGVJ32ywaA2zwLCzC', 'owner', '2025-08-04 15:21:31'),
(10, 'Aaditya Gawade', 'personsawant@gmail.com', '$2y$10$SCyf1lWwHomLQacMRB4JCuVbV1lylV0Bqjd4YUBDJkfojj0SmrGkC', 'user', '2025-09-01 05:29:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Indexes for table `floor_status`
--
ALTER TABLE `floor_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_owner_floor` (`owner_id`,`floor_number`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parking_requests`
--
ALTER TABLE `parking_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_requests` (`owner_id`);

--
-- Indexes for table `parking_slots`
--
ALTER TABLE `parking_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `floor_status`
--
ALTER TABLE `floor_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parking_requests`
--
ALTER TABLE `parking_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `parking_slots`
--
ALTER TABLE `parking_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parking_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_requests`
--
ALTER TABLE `parking_requests`
  ADD CONSTRAINT `parking_requests` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_slots`
--
ALTER TABLE `parking_slots`
  ADD CONSTRAINT `fk_slots_parking` FOREIGN KEY (`parking_id`) REFERENCES `parking_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
