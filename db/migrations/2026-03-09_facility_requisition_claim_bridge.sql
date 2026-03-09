-- Facility + Requisition claim bridge updates
-- Date: 2026-03-09

ALTER TABLE `facility_records_units`
  ADD COLUMN IF NOT EXISTS `facility_unit_manager_user_id` int(11) DEFAULT NULL AFTER `unit_name`;

ALTER TABLE `facility_records_assignments`
  ADD COLUMN IF NOT EXISTS `requisition_id` int(11) DEFAULT NULL AFTER `source_item_id`;

ALTER TABLE `requisition_items`
  ADD COLUMN IF NOT EXISTS `approved_by_user_id` int(11) DEFAULT NULL AFTER `reason`,
  ADD COLUMN IF NOT EXISTS `approved_at` datetime DEFAULT NULL AFTER `approved_by_user_id`,
  ADD COLUMN IF NOT EXISTS `claimed_by_user_id` int(11) DEFAULT NULL AFTER `approved_at`,
  ADD COLUMN IF NOT EXISTS `claimed_at` datetime DEFAULT NULL AFTER `claimed_by_user_id`,
  ADD COLUMN IF NOT EXISTS `claim_assignment_id` int(11) DEFAULT NULL AFTER `claimed_at`,
  ADD COLUMN IF NOT EXISTS `claim_facility_id` int(11) DEFAULT NULL AFTER `claim_assignment_id`,
  ADD COLUMN IF NOT EXISTS `claim_unit_id` int(11) DEFAULT NULL AFTER `claim_facility_id`;
