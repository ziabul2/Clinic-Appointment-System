-- Migration: add doctor_id to users table (optional)
-- Run this in your MySQL server if `users` table doesn't have `doctor_id` column.

ALTER TABLE users
ADD COLUMN IF NOT EXISTS doctor_id INT NULL AFTER role;

-- Optional: add foreign key if `doctors` table exists
ALTER TABLE users
ADD CONSTRAINT IF NOT EXISTS fk_users_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL ON UPDATE CASCADE;
