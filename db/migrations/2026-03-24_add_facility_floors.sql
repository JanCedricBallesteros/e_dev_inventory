-- Add JSON floors list per facility and optional floor label per unit
ALTER TABLE facility_records_facilities
    ADD COLUMN facility_floor LONGTEXT NOT NULL DEFAULT '{}' AFTER facility_name;

ALTER TABLE facility_records_units
    ADD COLUMN floor_label VARCHAR(100) NULL AFTER unit_name;
