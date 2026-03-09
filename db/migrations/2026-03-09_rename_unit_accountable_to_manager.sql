-- Rename legacy unit-level column to clearer manager naming.
-- Keeps item-level accountable_user_id in facility_records_assignments unchanged.
-- Date: 2026-03-09

SET @db_name = DATABASE();

SET @has_new = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'facility_records_units'
      AND COLUMN_NAME = 'facility_unit_manager_user_id'
);

SET @has_old = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'facility_records_units'
      AND COLUMN_NAME = 'accountable_user_id'
);

SET @sql = IF(
    @has_new = 0 AND @has_old = 1,
    'ALTER TABLE facility_records_units CHANGE COLUMN accountable_user_id facility_unit_manager_user_id INT(11) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_new_after = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'facility_records_units'
      AND COLUMN_NAME = 'facility_unit_manager_user_id'
);

SET @sql2 = IF(
    @has_new_after = 0,
    'ALTER TABLE facility_records_units ADD COLUMN facility_unit_manager_user_id INT(11) NULL AFTER unit_name',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

