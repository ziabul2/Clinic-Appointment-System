-- Migration: 007_appointment_counters_per_doctor.sql
-- Purpose: Make appointment_counters scoped per doctor (date + doctor_id)
-- Idempotent: safe to run multiple times

SET @db := DATABASE();

-- Add doctor_id column if not exists
SET @cnt := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointment_counters' AND COLUMN_NAME = 'doctor_id');
IF @cnt = 0 THEN
  ALTER TABLE `appointment_counters` ADD COLUMN `doctor_id` INT NULL AFTER `date`;
END IF;

-- Add unique index on (date, doctor_id)
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'appointment_counters' AND INDEX_NAME = 'uniq_date_doctor');
IF @idx = 0 THEN
  CREATE UNIQUE INDEX `uniq_date_doctor` ON `appointment_counters` (`date`, `doctor_id`);
END IF;

-- Note: existing rows will have doctor_id = NULL. New allocation logic should use doctor-specific rows.
