-- e_dev_inventory
-- Superadmin Employment Status schema migration
-- Date: 2026-03-04
-- Safe to run multiple times.

START TRANSACTION;

-- 1) Ensure employment_status table exists
CREATE TABLE IF NOT EXISTS `employment_status` (
  `employment_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_code` varchar(50) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employment_status_id`),
  UNIQUE KEY `uk_employment_status_code` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Seed required statuses
INSERT INTO `employment_status` (`status_code`, `status_name`, `description`)
SELECT 'Permanent', 'Permanent', 'Permanent Employment'
WHERE NOT EXISTS (
    SELECT 1 FROM `employment_status` WHERE `status_code` = 'Permanent'
);

INSERT INTO `employment_status` (`status_code`, `status_name`, `description`)
SELECT 'COS', 'Contract of Service', 'COS - Contractual Position'
WHERE NOT EXISTS (
    SELECT 1 FROM `employment_status` WHERE `status_code` = 'COS'
);

INSERT INTO `employment_status` (`status_code`, `status_name`, `description`)
SELECT 'JO', 'Job Order', 'JO - Job Order Position'
WHERE NOT EXISTS (
    SELECT 1 FROM `employment_status` WHERE `status_code` = 'JO'
);

-- 3) Ensure users.employment_status_id exists
SET @has_users_table := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'users'
);

SET @has_users_employment_status := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'employment_status_id'
);

SET @sql_add_users_col := IF(
    @has_users_table = 1 AND @has_users_employment_status = 0,
    'ALTER TABLE `users` ADD COLUMN `employment_status_id` int(11) NULL AFTER `position`',
    'SELECT ''users.employment_status_id already exists or users table missing'''
);
PREPARE stmt FROM @sql_add_users_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Ensure index exists on users.employment_status_id
SET @has_users_employment_status := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'employment_status_id'
);

SET @has_users_employment_idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'idx_users_employment_status_id'
);

SET @sql_add_idx := IF(
    @has_users_table = 1 AND @has_users_employment_status = 1 AND @has_users_employment_idx = 0,
    'ALTER TABLE `users` ADD INDEX `idx_users_employment_status_id` (`employment_status_id`)',
    'SELECT ''users employment_status index already exists or cannot be created'''
);
PREPARE stmt FROM @sql_add_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) Clean invalid references before FK (if any)
SET @sql_cleanup_invalid := IF(
    @has_users_table = 1 AND @has_users_employment_status = 1,
    'UPDATE `users` u
       LEFT JOIN `employment_status` e
              ON e.`employment_status_id` = u.`employment_status_id`
      SET u.`employment_status_id` = NULL
      WHERE u.`employment_status_id` IS NOT NULL
        AND e.`employment_status_id` IS NULL',
    'SELECT ''users employment_status cleanup skipped'''
);
PREPARE stmt FROM @sql_cleanup_invalid;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6) Ensure FK exists (users -> employment_status)
SET @has_fk_users_employment_status := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND constraint_name = 'fk_users_employment_status'
      AND constraint_type = 'FOREIGN KEY'
);

SET @sql_add_fk := IF(
    @has_users_table = 1
    AND @has_users_employment_status = 1
    AND @has_fk_users_employment_status = 0,
    'ALTER TABLE `users`
       ADD CONSTRAINT `fk_users_employment_status`
       FOREIGN KEY (`employment_status_id`)
       REFERENCES `employment_status` (`employment_status_id`)
       ON UPDATE CASCADE
       ON DELETE SET NULL',
    'SELECT ''users employment_status FK already exists or cannot be created'''
);
PREPARE stmt FROM @sql_add_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

