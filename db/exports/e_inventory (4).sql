-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 06:12 AM
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
-- Database: `e_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_assignments`
--

CREATE TABLE `facility_records_assignments` (
  `assignment_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `module_type` enum('AST','CSM','PERSONAL') NOT NULL,
  `source_item_id` int(11) DEFAULT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `item_code` varchar(120) NOT NULL,
  `item_description` text DEFAULT NULL,
  `qty` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) DEFAULT NULL,
  `issued_to_user_id` int(11) DEFAULT NULL,
  `accountable_user_id` int(11) DEFAULT NULL,
  `managed_by_user_id` int(11) DEFAULT NULL,
  `status` enum('ACTIVE','REPORTED','RETURN_REQUESTED','RETURNED','TRANSFERRED') NOT NULL DEFAULT 'ACTIVE',
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `returned_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_assignments`
--

INSERT INTO `facility_records_assignments` (`assignment_id`, `facility_id`, `unit_id`, `module_type`, `source_item_id`, `requisition_id`, `item_code`, `item_description`, `qty`, `unit`, `issued_to_user_id`, `accountable_user_id`, `managed_by_user_id`, `status`, `issued_at`, `returned_at`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'AST', 45, NULL, 'AST-ASDFG2E-0001', 'Intel Core i5 Desktop Computer Quad-Core 8 Thread 16GB RAM 512GB SSD Full Set Pc Computer (Pc, Monitor, Mouse, Keyboard)', 1.00, 'set', 3, 3, 1, 'ACTIVE', '2026-03-25 17:38:06', NULL, NULL, 1, 1, '2026-03-25 09:38:06', '2026-03-25 09:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_facilities`
--

CREATE TABLE `facility_records_facilities` (
  `facility_id` int(11) NOT NULL,
  `facility_code` varchar(50) NOT NULL,
  `facility_name` varchar(150) NOT NULL,
  `facility_floor` longtext NOT NULL DEFAULT '{}',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_facilities`
--

INSERT INTO `facility_records_facilities` (`facility_id`, `facility_code`, `facility_name`, `facility_floor`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'AB', 'Admin Building', '{\"floors\":[\"Left wing\",\"RIght wing\",\"Main\"]}', 1, 1, 1, '2026-03-09 01:37:08', '2026-03-25 08:55:15'),
(7, 'JMC', 'Joaquin Chipeco BLDG.', '{\"floors\":[\"JMC 1\",\"JMC 2\",\"JMC 3\"]}', 1, 1, 1, '2026-03-23 20:33:40', '2026-03-23 20:33:40'),
(8, 'R', 'Rizal Building', '{\"floors\":[\"Main\"]}', 1, 1, 1, '2026-03-25 08:51:36', '2026-03-26 00:25:06');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_history`
--

CREATE TABLE `facility_records_history` (
  `history_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_history`
--

INSERT INTO `facility_records_history` (`history_id`, `assignment_id`, `action`, `old_status`, `new_status`, `remarks`, `actor_user_id`, `created_at`) VALUES
(1, 1, 'ASSIGNED', NULL, 'ACTIVE', NULL, 1, '2026-03-25 09:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_units`
--

CREATE TABLE `facility_records_units` (
  `unit_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `unit_type` enum('ROOM','OFFICE','LABORATORY') NOT NULL DEFAULT 'ROOM',
  `unit_code` varchar(50) NOT NULL,
  `unit_name` varchar(150) NOT NULL,
  `floor_label` varchar(100) DEFAULT NULL,
  `facility_unit_manager_user_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_units`
--

INSERT INTO `facility_records_units` (`unit_id`, `facility_id`, `unit_type`, `unit_code`, `unit_name`, `floor_label`, `facility_unit_manager_user_id`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 7, 'ROOM', 'LIRC', 'Library', 'JMC 2', 11, 1, 1, 1, '2026-03-25 08:49:46', '2026-03-26 00:00:46'),
(2, 7, 'OFFICE', 'REG', 'Registrar', 'JMC 1', 3, 1, 1, 1, '2026-03-25 08:50:46', '2026-03-25 08:50:46'),
(3, 8, 'OFFICE', 'FC DCI', 'Faculty DCI', 'Main', 7, 1, 1, 1, '2026-03-25 08:52:23', '2026-03-26 00:25:17'),
(4, 1, 'OFFICE', 'MISD', 'Management Information System Department', 'Left wing', 1, 1, 1, 1, '2026-03-25 08:56:51', '2026-03-26 00:00:24');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_unit_managers`
--

CREATE TABLE `facility_records_unit_managers` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_unit_managers`
--

INSERT INTO `facility_records_unit_managers` (`id`, `unit_id`, `user_id`, `created_at`) VALUES
(3, 2, 3, '2026-03-25 08:50:46'),
(4, 2, 5, '2026-03-25 08:50:46'),
(9, 4, 1, '2026-03-26 00:00:25'),
(10, 4, 3, '2026-03-26 00:00:25'),
(11, 1, 11, '2026-03-26 00:00:46'),
(12, 1, 3, '2026-03-26 00:00:46'),
(13, 3, 7, '2026-03-26 00:25:18'),
(14, 3, 3, '2026-03-26 00:25:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `facility_records_assignments`
--
ALTER TABLE `facility_records_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_assignment_unit` (`unit_id`),
  ADD KEY `idx_assignment_status` (`status`),
  ADD KEY `idx_assignment_module` (`module_type`);

--
-- Indexes for table `facility_records_facilities`
--
ALTER TABLE `facility_records_facilities`
  ADD PRIMARY KEY (`facility_id`),
  ADD UNIQUE KEY `uk_facility_code` (`facility_code`);

--
-- Indexes for table `facility_records_history`
--
ALTER TABLE `facility_records_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_history_assignment` (`assignment_id`);

--
-- Indexes for table `facility_records_units`
--
ALTER TABLE `facility_records_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `uk_facility_unit_code` (`facility_id`,`unit_code`),
  ADD KEY `idx_unit_facility` (`facility_id`);

--
-- Indexes for table `facility_records_unit_managers`
--
ALTER TABLE `facility_records_unit_managers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_unit_user` (`unit_id`,`user_id`),
  ADD KEY `idx_unit` (`unit_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `facility_records_assignments`
--
ALTER TABLE `facility_records_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facility_records_facilities`
--
ALTER TABLE `facility_records_facilities`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `facility_records_history`
--
ALTER TABLE `facility_records_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facility_records_units`
--
ALTER TABLE `facility_records_units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `facility_records_unit_managers`
--
ALTER TABLE `facility_records_unit_managers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
