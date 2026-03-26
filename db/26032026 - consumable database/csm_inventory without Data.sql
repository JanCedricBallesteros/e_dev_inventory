-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 08:06 AM
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
-- Table structure for table `csm_audit_checks`
--

CREATE TABLE `csm_audit_checks` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `inventory_system_item_code` varchar(100) NOT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `item_description` text DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `item_cost` decimal(12,2) DEFAULT NULL,
  `source_of_funds` varchar(150) DEFAULT NULL,
  `system_unit_quantity` int(11) NOT NULL DEFAULT 0,
  `system_current_quantity` int(11) NOT NULL DEFAULT 0,
  `counted_quantity` int(11) NOT NULL DEFAULT 0,
  `variance_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_crit_level` int(11) NOT NULL DEFAULT 0,
  `system_status` tinyint(1) DEFAULT NULL,
  `status_at_check` varchar(100) NOT NULL DEFAULT '',
  `condition` varchar(50) NOT NULL DEFAULT '',
  `storage_location` varchar(150) NOT NULL DEFAULT '',
  `remarks` text DEFAULT NULL,
  `checked_by` int(11) NOT NULL,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csm_audit_sessions`
--

CREATE TABLE `csm_audit_sessions` (
  `id` int(11) NOT NULL,
  `series_code` varchar(50) NOT NULL,
  `audit_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Active','Closed') NOT NULL DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csm_inventory`
--

CREATE TABLE `csm_inventory` (
  `inventory_id` int(11) NOT NULL,
  `inventory_system_item_code` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `cost_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `source_of_funds` varchar(150) DEFAULT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `allowed_employment_status` text DEFAULT '{"none":true}',
  `quantity` int(11) NOT NULL COMMENT 'Actual quantity received',
  `current_quantity` int(11) NOT NULL COMMENT 'For quantity to be issued',
  `qty_crit_level` int(11) NOT NULL COMMENT 'Critical stock threshold',
  `last_updated` date NOT NULL,
  `item_category_img` varchar(255) DEFAULT NULL,
  `qr_verification` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_image_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csm_inventory_category`
--

CREATE TABLE `csm_inventory_category` (
  `category_id` int(11) NOT NULL,
  `item_category_name` varchar(150) NOT NULL,
  `category_image` varchar(255) DEFAULT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `category_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csm_inventory_category_images`
--

CREATE TABLE `csm_inventory_category_images` (
  `image_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `csm_audit_checks`
--
ALTER TABLE `csm_audit_checks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_item` (`session_id`,`inventory_system_item_code`),
  ADD KEY `idx_audit_session` (`session_id`),
  ADD KEY `idx_audit_inventory` (`inventory_id`),
  ADD KEY `idx_audit_checked_by` (`checked_by`);

--
-- Indexes for table `csm_audit_sessions`
--
ALTER TABLE `csm_audit_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_series_code` (`series_code`),
  ADD KEY `idx_audit_status` (`status`),
  ADD KEY `idx_audit_created_by` (`created_by`);

--
-- Indexes for table `csm_inventory`
--
ALTER TABLE `csm_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `inventory_system_item_code` (`inventory_system_item_code`);

--
-- Indexes for table `csm_inventory_category`
--
ALTER TABLE `csm_inventory_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `item_category_code` (`item_category_code`),
  ADD UNIQUE KEY `uniq_item_category_code` (`item_category_code`);

--
-- Indexes for table `csm_inventory_category_images`
--
ALTER TABLE `csm_inventory_category_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `csm_audit_checks`
--
ALTER TABLE `csm_audit_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csm_audit_sessions`
--
ALTER TABLE `csm_audit_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csm_inventory`
--
ALTER TABLE `csm_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csm_inventory_category`
--
ALTER TABLE `csm_inventory_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csm_inventory_category_images`
--
ALTER TABLE `csm_inventory_category_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
