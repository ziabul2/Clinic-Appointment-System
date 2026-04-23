-- Migration: Add profile_picture column to doctors
ALTER TABLE doctors
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL;
