-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2025 at 09:19 PM
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
-- Database: `transport app`
--

-- --------------------------------------------------------

--
-- Table structure for table `riders`
--

CREATE TABLE `riders` (
  `rider_id` text DEFAULT NULL,
  `name` text DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `vehicle` text DEFAULT NULL,
  `vehicle_type` text DEFAULT NULL,
  `phone` int(11) DEFAULT NULL,
  `license_plate` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `availability_status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riders`
--

INSERT INTO `riders` (`rider_id`, `name`, `amount`, `vehicle`, `vehicle_type`, `phone`, `license_plate`, `rating`, `availability_status`) VALUES
('1', 'Lorenzo', 1250, 'Impreza', 'Subaru', 710993545, 'KBW 475R', 4, 'available'),
('3', 'John Doe', 1450, 'Honda CB125F', 'Motorcycle', 555, 'KDA 789B', 5, 'available'),
('2', 'Jane Smith', 1300, 'Toyota Vitz', 'Car', 555, 'KAC 111C', 5, 'busy'),
('5', 'Peter Jones', 1100, 'Nissan Note', 'Car', 555, 'KAA 222D', 4, 'busy'),
('6', 'Sarah Lee', 1150, 'Tuk Tuk', 'Three-Wheeler', 555, 'KDE 333E', 5, 'available'),
('4', 'Michael Brown', 1050, 'Yamaha XJ6', 'Motorcycle', 555, 'KDD 444F', 5, 'offline');

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

CREATE TABLE `rides` (
  `ride_id` int(11) NOT NULL,
  `rider_id` text DEFAULT NULL,
  `passenger_id` int(11) NOT NULL,
  `destination` text DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `pickup_point` text DEFAULT NULL,
  `passenger_name` varchar(255) NOT NULL,
  `passenger_email` varchar(255) NOT NULL,
  `passenger_phone` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rides`
--

INSERT INTO `rides` (`ride_id`, `rider_id`, `passenger_id`, `destination`, `amount`, `pickup_point`, `passenger_name`, `passenger_email`, `passenger_phone`, `status`, `booking_date`) VALUES
(5, '1', 8765432, 'busan', 500, 'seoul', '', '', '', 'pending', '2025-07-10 19:15:33'),
(6, '2', 1, 'Destination A', 1300, 'Pickup Point B', '', '', '', 'confirmed', '2025-07-10 19:15:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `name` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `id_number` int(11) NOT NULL,
  `phone` int(11) NOT NULL,
  `role` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`name`, `email`, `password`, `id_number`, `phone`, `role`) VALUES
('Lorenzo', 'llwmuchiri@gmail.com', '$2y$10$my7/ByUGd/JLyuILfGozCuBOxXahkpaz/gQ9.5JVspY/A2PDChYyu', 1234567890, 1234567890, 'customer'),
('Ariana Madix', 'arianam@gmail.com', '$2y$10$TPsVw452rgc2b1/nUmNloO5Ws3uPpvWtcWVFKLOwjD9d8pNnteMuG', 8765432, 783261571, 'customer'),
('Yeat', 'disrespectful@gmail.com', '$2y$10$wfM8/SJI.4oeQN6tGQg9U.FT0ZaJdJHPT7nxiw6dv.fGgP4Zs0ErC', 2093, 87654322, 'customer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`ride_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `ride_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
