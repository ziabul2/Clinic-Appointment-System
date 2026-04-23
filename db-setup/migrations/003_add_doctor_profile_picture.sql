-- Migration 003: Add doctor profile picture
ALTER TABLE doctors
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER available_time_end;
