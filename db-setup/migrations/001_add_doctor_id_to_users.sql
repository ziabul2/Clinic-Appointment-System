-- Migration 001: Add doctor_id to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS doctor_id INT NULL AFTER role;

ALTER TABLE users
ADD CONSTRAINT IF NOT EXISTS fk_users_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL ON UPDATE CASCADE;
