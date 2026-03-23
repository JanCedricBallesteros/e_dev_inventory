-- Create join table for multiple facility unit managers
CREATE TABLE IF NOT EXISTS facility_records_unit_managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_unit_user (unit_id, user_id),
    KEY idx_unit (unit_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
