-- Facility Inventory Records core tables
-- Date: 2026-03-09

CREATE TABLE IF NOT EXISTS `facility_records_facilities` (
  `facility_id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_code` varchar(50) NOT NULL,
  `facility_name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`facility_id`),
  UNIQUE KEY `uk_facility_code` (`facility_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `facility_records_units` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) NOT NULL,
  `unit_type` enum('ROOM','OFFICE','LABORATORY','OTHER') NOT NULL DEFAULT 'ROOM',
  `unit_code` varchar(50) NOT NULL,
  `unit_name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `uk_facility_unit_code` (`facility_id`,`unit_code`),
  KEY `idx_unit_facility` (`facility_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `facility_records_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `module_type` enum('AST','CSM','PERSONAL') NOT NULL,
  `source_item_id` int(11) DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  KEY `idx_assignment_unit` (`unit_id`),
  KEY `idx_assignment_status` (`status`),
  KEY `idx_assignment_module` (`module_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `facility_records_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_history_assignment` (`assignment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
