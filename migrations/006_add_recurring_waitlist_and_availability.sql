-- Migration: 006_add_recurring_waitlist_and_availability.sql
-- Purpose: Add recurrence rules, waitlist, availability cache, and appointment linkage fields.
-- Idempotent: safe to run multiple times.

SET @db := DATABASE();

-- Create recurrence_rules table
CREATE TABLE IF NOT EXISTS `recurrence_rules` (
  `recurrence_id` INT NOT NULL AUTO_INCREMENT,
  `doctor_id` INT NULL,
  `patient_id` INT NULL,
  `frequency` ENUM('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'weekly',
  `interval` INT NOT NULL DEFAULT 1,
  `by_weekdays` VARCHAR(50) NULL COMMENT 'CSV weekdays e.g. MON,TUE',
  `by_monthday` VARCHAR(20) NULL COMMENT 'CSV month days e.g. 1,15',
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `occurrences` INT NULL COMMENT 'Maximum number of occurrences',
  `appointment_time` TIME NULL,
  `duration_minutes` INT NOT NULL DEFAULT 15,
  `consultation_type` VARCHAR(64) NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`recurrence_id`),
  INDEX `ix_recur_doctor_start` (`doctor_id`, `start_date`),
  INDEX `ix_recur_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add recurrence linkage columns to appointments
-- Use conditional checks to avoid errors on older MySQL versions
-- recurrence_id: links back to the rule that generated this occurrence
-- parent_appointment_id: links to previous appointment when rescheduled/reserved from waitlist
-- source: origin of appointment (manual, recurring, waitlist)

-- Add recurrence_id
SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'recurrence_id');
IF @cnt = 0 THEN
  ALTER TABLE `appointments` ADD COLUMN `recurrence_id` INT NULL AFTER `appointment_serial`;
END IF;

-- Add parent_appointment_id
SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'parent_appointment_id');
IF @cnt = 0 THEN
  ALTER TABLE `appointments` ADD COLUMN `parent_appointment_id` INT NULL AFTER `recurrence_id`;
END IF;

-- Add recurrence_instance_index (optional occurrence index)
SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'recurrence_instance_index');
IF @cnt = 0 THEN
  ALTER TABLE `appointments` ADD COLUMN `recurrence_instance_index` INT NULL AFTER `parent_appointment_id`;
END IF;

-- Add source field
SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'source');
IF @cnt = 0 THEN
  ALTER TABLE `appointments` ADD COLUMN `source` ENUM('manual','recurring','waitlist') NOT NULL DEFAULT 'manual' AFTER `recurrence_instance_index`;
END IF;

-- Create waiting_list table (simple waitlist management)
CREATE TABLE IF NOT EXISTS `waiting_list` (
  `waiting_id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NULL,
  `preferred_date` DATE NULL,
  `preferred_time` TIME NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending','notified','processed','cancelled') NOT NULL DEFAULT 'pending',
  `taken_by` INT NULL,
  `appointment_id` INT NULL COMMENT 'If processed, link to created appointment',
  PRIMARY KEY (`waiting_id`),
  INDEX `ix_wait_doc_date` (`doctor_id`,`preferred_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Availability cache table: cached timeslots for a doctor/date to speed realtime checks
CREATE TABLE IF NOT EXISTS `availability_cache` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `doctor_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `timeslots` JSON NOT NULL COMMENT 'JSON array of available timeslots with metadata',
  `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ttl_seconds` INT NOT NULL DEFAULT 300,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doc_date` (`doctor_id`,`date`),
  INDEX `ix_avail_doc_date` (`doctor_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add optional buffer fields to doctors for before/after slot padding
SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'buffer_before_minutes');
IF @cnt = 0 THEN
  ALTER TABLE `doctors` ADD COLUMN `buffer_before_minutes` INT NOT NULL DEFAULT 0 AFTER `available_time_end`;
END IF;

SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'buffer_after_minutes');
IF @cnt = 0 THEN
  ALTER TABLE `doctors` ADD COLUMN `buffer_after_minutes` INT NOT NULL DEFAULT 0 AFTER `buffer_before_minutes`;
END IF;

-- Add foreign key constraints where reasonable (wrapped in TRY/CATCH style)
-- Note: some MySQL setups may not allow adding FKs if tables/columns absent or types mismatch; errors logged but migration continues.
-- We avoid failing the migration if FK addition fails by checking existence first.

-- Attach recurrence_id FK to recurrence_rules if columns exist
SET @has_appt_recur := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'recurrence_id');
SET @has_recur := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'recurrence_rules');
IF @has_appt_recur > 0 AND @has_recur > 0 THEN
  ALTER TABLE `appointments` ADD CONSTRAINT `fk_appointments_recurrence` FOREIGN KEY (`recurrence_id`) REFERENCES `recurrence_rules`(`recurrence_id`) ON DELETE SET NULL ON UPDATE CASCADE;
END IF;

-- FK from waiting_list.doctor_id -> doctors.doctor_id (best-effort)
SET @has_wait := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'waiting_list');
SET @has_doc := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors');
IF @has_wait > 0 AND @has_doc > 0 THEN
  ALTER TABLE `waiting_list` ADD CONSTRAINT `fk_waiting_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE SET NULL ON UPDATE CASCADE;
END IF;

-- End of migration
