-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 08:21 AM
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

--
-- Dumping data for table `csm_audit_checks`
--

INSERT INTO `csm_audit_checks` (`id`, `session_id`, `inventory_id`, `inventory_system_item_code`, `item_category_code`, `item_description`, `acquisition_date`, `item_cost`, `source_of_funds`, `system_unit_quantity`, `system_current_quantity`, `counted_quantity`, `variance_quantity`, `unit_crit_level`, `system_status`, `status_at_check`, `condition`, `storage_location`, `remarks`, `checked_by`, `checked_at`) VALUES
(1, 1, 7, 'CSM-0001-0001', 'CSM0001', 'Bulk test', '2026-02-27', 25.50, 'General Fund', 59, 40, 40, 0, 60, 2, 'Unavailable', 'Good', 'test storage', 'good lah', 1, '2026-03-12 10:15:32'),
(2, 1, 6, 'CSM-0002-0001', 'CSM0002', 'test', '2026-02-27', 22.00, 'test', 0, 0, 10, 10, 2, 3, 'Unavailable', 'Good', 'test', '', 1, '2026-03-12 13:24:48'),
(3, 1, 8, 'CSM-0001-0003', 'CSM0001', 'Example itemized description (full details/specs/notes)', '2026-02-27', 25.50, 'test', 111, 22, 18, -4, 10, 1, 'Available', 'Missing', '', '', 1, '2026-03-12 13:26:28');

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

--
-- Dumping data for table `csm_audit_sessions`
--

INSERT INTO `csm_audit_sessions` (`id`, `series_code`, `audit_name`, `start_date`, `end_date`, `status`, `created_by`, `created_at`) VALUES
(1, 'CSM-PC-2026-001', 'march test 2026', '2026-03-12', '2026-03-14', 'Active', 1, '2026-03-12 02:14:48');

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
  `allowed_employment_status` text DEFAULT NULL,
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

--
-- Dumping data for table `csm_inventory`
--

INSERT INTO `csm_inventory` (`inventory_id`, `inventory_system_item_code`, `item_description`, `acquisition_date`, `cost_value`, `unit`, `source_of_funds`, `item_category_code`, `status`, `allowed_employment_status`, `quantity`, `current_quantity`, `qty_crit_level`, `last_updated`, `item_category_img`, `qr_verification`, `created_at`, `updated_at`, `category_image_id`) VALUES
(6, 'CSM-0002-0001', 'test', '2026-02-27', 22.00, 'pcs', 'test', 'CSM0002', 3, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 0, 0, 2, '2026-03-10', NULL, NULL, '2026-02-26 21:24:28', '2026-03-10 07:09:12', NULL),
(7, 'CSM-0001-0001', 'Bulk test', '2026-02-27', 25.50, 'pcs', 'General Fund', 'CSM0001', 2, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 59, 40, 60, '2026-03-10', '8', NULL, '2026-02-26 21:52:49', '2026-03-10 06:21:56', NULL),
(8, 'CSM-0001-0003', 'Example itemized description (full details/specs/notes)', '2026-02-27', 25.50, 'pcs', 'test', 'CSM0001', 1, '[1]', 111, 22, 10, '2026-02-27', '7', NULL, '2026-02-26 22:00:54', '2026-03-10 05:23:59', NULL),
(9, 'CSM-0002-0004', 'test', '2026-02-27', 22.00, 'pcs', '', 'CSM0002', 0, '{\"none\":true}', 22, 21, 2, '2026-03-10', NULL, NULL, '2026-02-26 22:00:54', '2026-03-10 05:49:14', NULL),
(10, 'CSM-0002-0005', 'test incr', '2026-02-27', 22.00, 'pcs', 'test', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 41, 40, 2, '2026-03-10', NULL, NULL, '2026-02-27 03:06:44', '2026-03-10 01:24:32', NULL),
(12, 'CSM-0002-0006', 'test', '2026-03-06', 22.00, 'pcs', '2', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 24, 24, 2, '2026-03-10', NULL, NULL, '2026-03-06 04:50:45', '2026-03-10 01:24:09', NULL),
(13, 'CSM-0002-0007', 'item 4', '2026-03-06', 33.00, 'pcs', 'test2', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 35, 22, 5, '2026-03-10', NULL, NULL, '2026-03-06 05:46:01', '2026-03-10 01:23:50', NULL),
(14, 'CSM-0004-0001', 'test', '2026-03-11', 1000.00, 'pcs', 'test2', 'CSM0004', 2, NULL, 100, 10, 10, '2026-03-11', NULL, NULL, '2026-03-11 08:22:46', '2026-03-11 08:23:43', NULL),
(15, 'CSM-0002-0008', 'test log', '2026-03-19', 22.00, 'pcs', 'test', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 26, 6, 2, '2026-03-22', NULL, NULL, '2026-03-19 07:00:50', '2026-03-22 13:45:32', NULL),
(16, 'CSM-0002-0009', 'test log again', '2026-03-22', 22.00, 'pcs', 'test', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 29, 27, 22, '2026-03-22', NULL, NULL, '2026-03-22 13:57:25', '2026-03-22 14:25:28', NULL),
(17, 'CSM-0002-0010', 'test log 888', '2026-03-22', 33.00, 'pcs', 'test', 'CSM0002', 2, NULL, 25, 21, 22, '2026-03-23', NULL, NULL, '2026-03-22 14:54:41', '2026-03-22 16:13:29', NULL),
(18, 'CSM-0002-0011', 'test new avail', '2026-03-23', 534.00, 'box', 'test', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 99, 85, 5, '2026-03-23', NULL, NULL, '2026-03-23 00:48:53', '2026-03-23 06:42:05', NULL);

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

--
-- Dumping data for table `csm_inventory_category`
--

INSERT INTO `csm_inventory_category` (`category_id`, `item_category_name`, `category_image`, `item_category_code`, `category_photo`, `created_at`, `updated_at`) VALUES
(1, 'Office Supplies', NULL, 'CSM0001', 'cat_20260203_093756_ffc46e7579da.jpg', '2026-02-02 05:08:46', '2026-02-27 03:05:47'),
(2, 'Ballpoint Pen', NULL, 'CSM0002', NULL, '2026-02-02 06:00:58', '2026-02-27 03:05:51'),
(3, 'Pencils', NULL, 'CSM0003', 'cat_20260203_093014_150cd0d36aa6.jpg', '2026-02-03 00:58:28', '2026-02-27 03:05:55'),
(4, 'Ink Cartridge', NULL, 'CSM0004', NULL, '2026-02-03 02:52:31', '2026-02-27 03:05:59'),
(25, 'test log', NULL, 'CSM0005', NULL, '2026-03-19 07:09:32', '2026-03-19 07:09:32'),
(26, 'test row 765', NULL, 'CSM0022', NULL, '2026-03-22 13:46:23', '2026-03-22 13:46:23');

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
-- Dumping data for table `csm_inventory_category_images`
--

INSERT INTO `csm_inventory_category_images` (`image_id`, `category_id`, `file_name`, `file_url`, `is_primary`, `created_at`) VALUES
(6, 1, 'cat_1_1772155137_0_IMG20260216151809.jpg', '', 1, '2026-02-27 01:18:57'),
(7, 1, 'cat_1_1772156511_0_IMG20260219143716.jpg', 'upload/category/cat_1_1772156511_0_IMG20260219143716.jpg', 0, '2026-02-27 01:41:51'),
(8, 1, 'cat_1_1772156514_0_IMG20260219122939.jpg', 'upload/category/cat_1_1772156514_0_IMG20260219122939.jpg', 0, '2026-02-27 01:41:54'),
(11, 25, 'cat_25_20260319150932_1b6cb540dfea_652671908_973702628675826_8529131322222278439_n.jpg', 'upload/category/cat_25_20260319150932_1b6cb540dfea_652671908_973702628675826_8529131322222278439_n.jpg', 1, '2026-03-19 07:09:32');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `csm_audit_sessions`
--
ALTER TABLE `csm_audit_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `csm_inventory`
--
ALTER TABLE `csm_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `csm_inventory_category`
--
ALTER TABLE `csm_inventory_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `csm_inventory_category_images`
--
ALTER TABLE `csm_inventory_category_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
