-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 08:07 AM
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

--
-- Dumping data for table `csm_inventory`
--

INSERT INTO `csm_inventory` (`inventory_id`, `inventory_system_item_code`, `item_description`, `acquisition_date`, `cost_value`, `unit`, `source_of_funds`, `item_category_code`, `status`, `allowed_employment_status`, `quantity`, `current_quantity`, `qty_crit_level`, `last_updated`, `item_category_img`, `qr_verification`, `created_at`, `updated_at`, `category_image_id`) VALUES
(1, 'CSM-0001-0001', 'Bond paper', '2026-03-25', 0.00, 'box', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 5, '2026-03-25', '19', NULL, '2026-03-25 07:47:33', '2026-03-26 05:24:02', NULL),
(2, 'CSM-0001-0002', 'Pens', '2026-03-25', 0.00, 'pcs', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 30, 20, 5, '2026-03-25', '18', NULL, '2026-03-25 07:48:32', '2026-03-26 05:23:11', NULL),
(3, 'CSM-0001-0003', 'Pencil', '2026-03-25', 0.00, 'pcs', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 50, 40, 10, '2026-03-25', '13', NULL, '2026-03-25 07:49:32', '2026-03-26 05:22:06', NULL),
(4, 'CSM-0001-0004', 'markers', '2026-03-25', 0.00, 'pack', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', '14', NULL, '2026-03-25 07:51:23', '2026-03-26 05:21:43', NULL),
(5, 'CSM-0001-0005', 'folder', '2026-03-25', 0.00, 'pcs', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', '20', NULL, '2026-03-25 07:53:13', '2026-03-26 05:24:07', NULL),
(6, 'CSM-0001-0006', 'envelops', '2026-03-25', 0.00, 'pcs', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', '21', NULL, '2026-03-25 07:53:57', '2026-03-26 05:24:44', NULL),
(7, 'CSM-0001-0007', 'stapler wires', '2026-03-25', 0.00, 'box', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 150, 140, 10, '2026-03-25', '17', NULL, '2026-03-25 07:54:48', '2026-03-26 05:22:00', NULL),
(8, 'CSM-0001-0008', 'sticky notes', '2026-03-25', 0.00, 'box', '', 'CSM0001', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 300, 250, 50, '2026-03-25', '22', NULL, '2026-03-25 07:55:19', '2026-03-26 05:25:15', NULL),
(9, 'CSM-0002-0001', 'chalk', '2026-03-25', 0.00, 'pack', '', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 10, '2026-03-25', NULL, NULL, '2026-03-25 07:56:57', '2026-03-25 08:01:41', NULL),
(10, 'CSM-0002-0002', 'Whiteboard Markers', '2026-03-25', 0.00, 'pack', '', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 150, 140, 10, '2026-03-25', NULL, NULL, '2026-03-25 07:57:30', '2026-03-25 08:01:41', NULL),
(11, 'CSM-0002-0003', 'Chalk Erasers', '2026-03-25', 0.00, 'pcs', '', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 50, 40, 10, '2026-03-25', NULL, NULL, '2026-03-25 07:58:25', '2026-03-25 08:01:41', NULL),
(12, 'CSM-0002-0004', 'Whiteboard Erasers', '2026-03-25', 0.00, 'pcs', '', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 10, '2026-03-25', NULL, NULL, '2026-03-25 07:58:56', '2026-03-25 08:01:41', NULL),
(13, 'CSM-0002-0005', 'Manila Papers', '2026-03-25', 0.00, 'pack', '', 'CSM0002', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 10, '2026-03-25', NULL, NULL, '2026-03-25 07:59:27', '2026-03-25 08:01:41', NULL),
(14, 'CSM-0003-0001', 'Printer Ink', '2026-03-25', 0.00, 'pack', '', 'CSM0003', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:01:26', '2026-03-25 08:01:41', NULL),
(15, 'CSM-0003-0002', 'Ink Bottles', '2026-03-25', 0.00, 'box', '', 'CSM0003', 1, NULL, 90, 80, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:02:32', '2026-03-25 08:02:32', NULL),
(16, 'CSM-0004-0001', 'Detergent', '2026-03-25', 0.00, 'pcs', '', 'CSM0004', 1, NULL, 40, 30, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:04:10', '2026-03-25 08:04:10', NULL),
(17, 'CSM-0004-0002', 'Bleach', '2026-03-25', 0.00, 'pcs', '', 'CSM0004', 1, NULL, 50, 40, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:04:31', '2026-03-25 08:04:31', NULL),
(18, 'CSM-0004-0003', 'Disinfectant', '2026-03-25', 0.00, 'box', '', 'CSM0004', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:23:04', '2026-03-25 08:23:19', NULL),
(19, 'CSM-0004-0004', 'Trash Bags', '2026-03-25', 0.00, 'pcs', '', 'CSM0004', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:31:02', '2026-03-25 08:32:25', NULL),
(20, 'CSM-0004-0005', 'Floor Cleaner', '2026-03-25', 0.00, 'box', '', 'CSM0004', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:33:05', '2026-03-25 08:33:22', NULL),
(21, 'CSM-0004-0006', 'Air Freshener', '2026-03-25', 0.00, 'pack', '', 'CSM0004', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:51:15', '2026-03-25 08:54:25', NULL),
(22, 'CSM-0004-0007', 'Gloves', '2026-03-25', 0.00, 'pack', '', 'CSM0004', 1, '{\"teaching\":[1,1,2,2,3,3],\"non_teaching\":[1,1,2,2,3,3]}', 20, 15, 10, '2026-03-26', NULL, NULL, '2026-03-25 08:54:05', '2026-03-26 04:26:32', NULL),
(23, 'CSM-0005-0001', 'Tissue Roll', '2026-03-25', 0.00, 'box', '', 'CSM0005', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 30, 20, 10, '2026-03-25', NULL, NULL, '2026-03-25 08:55:13', '2026-03-25 08:55:29', NULL),
(24, 'CSM-0005-0002', 'Hand Soap', '2026-03-25', 0.00, 'pcs', '', 'CSM0005', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 50, 40, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:06:18', '2026-03-25 09:15:36', NULL),
(25, 'CSM-0005-0003', 'Paper Towel Rolls', '2026-03-25', 0.00, 'box', '', 'CSM0005', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 70, 60, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:06:42', '2026-03-25 09:15:43', NULL),
(26, 'CSM-0005-0004', 'Disposal Bags', '2026-03-25', 0.00, 'pack', '', 'CSM0005', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 100, 90, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:07:07', '2026-03-25 09:15:50', NULL),
(27, 'CSM-0006-0001', 'Cotton', '2026-03-25', 0.00, 'box', '', 'CSM0006', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 40, 30, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:13:28', '2026-03-25 09:15:56', NULL),
(28, 'CSM-0006-0002', 'Gauze', '2026-03-25', 0.00, 'box', '', 'CSM0006', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 20, 15, 5, '2026-03-25', NULL, NULL, '2026-03-25 09:13:50', '2026-03-25 09:16:03', NULL),
(29, 'CSM-0006-0003', 'Sanitized Alcohol', '2026-03-25', 0.00, 'pcs', '', 'CSM0006', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 33, 30, 5, '2026-03-25', NULL, NULL, '2026-03-25 09:14:21', '2026-03-25 09:16:08', NULL),
(30, 'CSM-0006-0004', 'Sanitized Gloves', '2026-03-25', 0.00, 'pack', '', 'CSM0006', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 30, 20, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:14:51', '2026-03-25 09:16:13', NULL),
(31, 'CSM-0006-0005', 'Antiseptic Bottle', '2026-03-25', 0.00, 'pcs', '', 'CSM0006', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 35, 25, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:15:18', '2026-03-25 09:16:18', NULL),
(32, 'CSM-0007-0001', 'Batteries', '2026-03-25', 0.00, 'pack', '', 'CSM0007', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:22:30', '2026-03-25 09:37:13', NULL),
(33, 'CSM-0007-0002', 'Electric Tape', '2026-03-25', 0.00, 'pack', '', 'CSM0007', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 90, 80, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:24:39', '2026-03-25 09:37:19', NULL),
(34, 'CSM-0007-0003', 'Cable Ties', '2026-03-25', 0.00, 'pack', '', 'CSM0007', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 30, 20, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:25:21', '2026-03-25 09:37:27', NULL),
(35, 'CSM-0007-0004', 'Screws', '2026-03-25', 0.00, 'pack', '', 'CSM0007', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 78, 60, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:25:53', '2026-03-25 09:37:35', NULL),
(36, 'CSM-0008-0001', 'Cleaning Rags', '2026-03-25', 0.00, 'pcs', '', 'CSM0008', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 10, 8, 2, '2026-03-25', NULL, NULL, '2026-03-25 09:26:32', '2026-03-25 09:37:41', NULL),
(37, 'CSM-0008-0002', 'Mineral Water', '2026-03-25', 0.00, 'pack', '', 'CSM0008', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 22, 20, 5, '2026-03-25', NULL, NULL, '2026-03-25 09:27:34', '2026-03-25 09:37:48', NULL),
(38, 'CSM-0008-0003', 'Disposable Cups', '2026-03-25', 0.00, 'pack', '', 'CSM0008', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 37, 20, 9, '2026-03-25', NULL, NULL, '2026-03-25 09:30:54', '2026-03-25 09:38:00', NULL),
(39, 'CSM-0008-0004', 'Paper Plates', '2026-03-25', 0.00, 'pack', '', 'CSM0008', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 38, 20, 9, '2026-03-25', NULL, NULL, '2026-03-25 09:31:28', '2026-03-25 09:38:06', NULL),
(40, 'CSM-0008-0005', 'Disposable Utensils', '2026-03-25', 0.00, 'pack', '', 'CSM0008', 1, '{\"teaching\":[1,2,3],\"non_teaching\":[1,2,3]}', 55, 40, 10, '2026-03-25', NULL, NULL, '2026-03-25 09:32:00', '2026-03-25 09:38:11', NULL),
(41, 'CSM-0008-0006', 'Napkins', '2026-03-25', 0.00, 'pack', '', 'CSM0008', 0, '{\"none\":true}', 44, 30, 9, '2026-03-26', NULL, NULL, '2026-03-25 09:32:22', '2026-03-26 05:35:11', NULL),
(42, 'CSM-0009-0001', 'Triple A Batteries', '2026-03-25', 0.00, 'pack', '', 'CSM0009', 3, '{\"teaching\":[1,1,2,2,3,3],\"non_teaching\":[1,1,2,2,3,3]}', 70, 0, 10, '2026-03-26', NULL, NULL, '2026-03-25 09:36:55', '2026-03-26 05:34:53', NULL),
(43, 'CSM-0009-0002', 'blank DVDs', '2026-03-26', 0.00, 'pcs', '', 'CSM0009', 2, '{\"teaching\":[1,1,2,2,3,3],\"non_teaching\":[1,1,2,2,3,3]}', 50, 5, 10, '2026-03-26', NULL, NULL, '2026-03-26 05:12:17', '2026-03-26 05:34:45', NULL),
(44, 'CSM-0002-0006', 'test', '2026-03-26', 0.00, 'pack', '', 'CSM0002', 2, '{\"teaching\":[1,1,2,2,3,3],\"non_teaching\":[1,1,2,2,3,3]}', 10, 5, 5, '2026-03-26', NULL, NULL, '2026-03-26 06:21:56', '2026-03-26 06:22:30', NULL);

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
(1, 'Office Supplies', NULL, 'CSM0001', NULL, '2026-03-25 07:45:14', '2026-03-25 07:45:14'),
(2, 'Classroom Supplies', NULL, 'CSM0002', NULL, '2026-03-25 07:45:14', '2026-03-25 07:45:14'),
(3, 'Printing Supplies', NULL, 'CSM0003', NULL, '2026-03-25 07:45:14', '2026-03-25 07:45:14'),
(4, 'Janitorial Supplies', NULL, 'CSM0004', NULL, '2026-03-25 07:45:14', '2026-03-25 07:45:14'),
(5, 'Restroom Supplies', NULL, 'CSM0005', NULL, '2026-03-25 07:45:14', '2026-03-25 07:45:14'),
(6, 'Clinic / First Aid Supplies', NULL, 'CSM0006', NULL, '2026-03-25 07:45:15', '2026-03-25 07:45:15'),
(7, 'Electrical / Maintenance Supplies', NULL, 'CSM0007', NULL, '2026-03-25 07:45:15', '2026-03-25 07:45:15'),
(8, 'Canteen / Pantry Supplies', NULL, 'CSM0008', NULL, '2026-03-25 07:45:15', '2026-03-25 07:45:15'),
(9, 'ICT / Computer Consumables', NULL, 'CSM0009', NULL, '2026-03-25 07:45:15', '2026-03-25 07:45:15');

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
(13, 1, 'cat_1_1774502433_0_shopping__1_.webp', 'upload/category/cat_1_1774502433_0_shopping__1_.webp', 0, '2026-03-26 05:20:33'),
(14, 1, 'cat_1_1774502436_0_shopping.webp', 'upload/category/cat_1_1774502436_0_shopping.webp', 0, '2026-03-26 05:20:36'),
(17, 1, 'cat_1_1774502462_0_23-15-scaled.jpg', 'upload/category/cat_1_1774502462_0_23-15-scaled.jpg', 0, '2026-03-26 05:21:02'),
(18, 1, 'cat_1_1774502584_0_109198_r_1.webp', 'upload/category/cat_1_1774502584_0_109198_r_1.webp', 0, '2026-03-26 05:23:04'),
(19, 1, 'cat_1_1774502634_0_1.jpg', 'upload/category/cat_1_1774502634_0_1.jpg', 0, '2026-03-26 05:23:54'),
(20, 1, 'cat_1_1774502637_0_8591_3586.jpg', 'upload/category/cat_1_1774502637_0_8591_3586.jpg', 0, '2026-03-26 05:23:57'),
(21, 1, 'cat_1_1774502671_0_envelops.jpg', 'upload/category/cat_1_1774502671_0_envelops.jpg', 0, '2026-03-26 05:24:31'),
(22, 1, 'cat_1_1774502710_0_5405_5405.jpg', 'upload/category/cat_1_1774502710_0_5405_5405.jpg', 0, '2026-03-26 05:25:10'),
(23, 1, 'cat_1_1774502740_0_office_supplies-min-1024x684.jpg', 'upload/category/cat_1_1774502740_0_office_supplies-min-1024x684.jpg', 1, '2026-03-26 05:25:40');

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
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `csm_inventory_category`
--
ALTER TABLE `csm_inventory_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `csm_inventory_category_images`
--
ALTER TABLE `csm_inventory_category_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
