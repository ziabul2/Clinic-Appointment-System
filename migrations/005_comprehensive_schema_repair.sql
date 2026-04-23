-- ============================================================================
-- Comprehensive Schema Repair and Migration for clinic_management Database
-- Date: 2025-11-25
-- Purpose: Align current database schema with backup reference and add
--          missing tables, columns, and constraints
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET AUTOCOMMIT = 1;

-- ============================================================================
-- PHASE 1: Add missing columns to appointments table
-- ============================================================================

-- Add missing columns to appointments table (payment tracking and medical details)
ALTER TABLE `appointments`
ADD COLUMN IF NOT EXISTS `payment_status` enum('pending','paid','partial','refunded') DEFAULT 'pending' COMMENT 'Payment status for the appointment' AFTER `status`,
ADD COLUMN IF NOT EXISTS `payment_method` varchar(50) DEFAULT NULL COMMENT 'Payment method used (e.g., cash, card, insurance)' AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `amount_paid` decimal(10,2) DEFAULT 0.00 COMMENT 'Amount paid for this appointment' AFTER `payment_method`,
ADD COLUMN IF NOT EXISTS `payment_notes` text DEFAULT NULL COMMENT 'Additional notes about payment' AFTER `amount_paid`,
ADD COLUMN IF NOT EXISTS `payment_date` datetime DEFAULT NULL COMMENT 'Date and time of payment' AFTER `payment_notes`,
ADD COLUMN IF NOT EXISTS `consultation_fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Consultation fee for this appointment' AFTER `payment_date`,
ADD COLUMN IF NOT EXISTS `urgency_level` enum('low','normal','high','emergency') DEFAULT 'normal' COMMENT 'Medical urgency level' AFTER `consultation_fee`,
ADD COLUMN IF NOT EXISTS `estimated_duration` int(11) DEFAULT 30 COMMENT 'Estimated duration in minutes' AFTER `urgency_level`,
ADD COLUMN IF NOT EXISTS `diagnosis` text DEFAULT NULL COMMENT 'Medical diagnosis after consultation' AFTER `estimated_duration`,
ADD COLUMN IF NOT EXISTS `prescription` text DEFAULT NULL COMMENT 'Prescription notes (legacy field; see prescriptions table for new records)' AFTER `diagnosis`,
ADD COLUMN IF NOT EXISTS `is_admitted` tinyint(1) DEFAULT 0 COMMENT 'Whether patient was admitted' AFTER `prescription`,
ADD COLUMN IF NOT EXISTS `admission_notes` text DEFAULT NULL COMMENT 'Admission details' AFTER `is_admitted`,
ADD COLUMN IF NOT EXISTS `admission_date` datetime DEFAULT NULL COMMENT 'Date and time of admission' AFTER `admission_notes`,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp' AFTER `created_at`;

-- Add indices for performance
ALTER TABLE `appointments`
ADD INDEX IF NOT EXISTS `idx_payment_status` (`payment_status`),
ADD INDEX IF NOT EXISTS `idx_urgency_level` (`urgency_level`),
ADD INDEX IF NOT EXISTS `idx_is_admitted` (`is_admitted`),
ADD INDEX IF NOT EXISTS `idx_updated_at` (`updated_at`);

-- ============================================================================
-- PHASE 2: Add performance indices on related tables
-- ============================================================================

ALTER TABLE `patients`
ADD INDEX IF NOT EXISTS `idx_email` (`email`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_gender` (`gender`),
ADD INDEX IF NOT EXISTS `idx_date_of_birth` (`date_of_birth`);

ALTER TABLE `doctors`
ADD INDEX IF NOT EXISTS `idx_specialization` (`specialization`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

ALTER TABLE `users`
ADD INDEX IF NOT EXISTS `idx_role` (`role`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- Add indices on prescriptions table
ALTER TABLE `prescriptions`
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_created_by` (`created_by`);

-- ============================================================================
-- PHASE 3: Re-enable foreign key checks
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Summary of Changes Applied
-- ============================================================================
-- This migration adds:
-- 1. 14 missing columns to appointments table:
--    - Payment: payment_status, payment_method, amount_paid, payment_notes, payment_date, consultation_fee
--    - Medical: urgency_level, estimated_duration, diagnosis, prescription, is_admitted, admission_notes, admission_date
--    - Audit: updated_at timestamp
-- 2. Performance indices on appointments, patients, doctors, users, prescriptions
-- 3. All changes are idempotent (safe to run multiple times)
--
-- Note: waiting_list table already exists (created by backup/previous migrations)
-- ============================================================================

-- EOF: 005_comprehensive_schema_repair.sql
