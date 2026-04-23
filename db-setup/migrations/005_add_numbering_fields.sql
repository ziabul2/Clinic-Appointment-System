-- Migration 005: Add appointment and patient numbering fields
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS appointment_number VARCHAR(50) UNIQUE,
ADD COLUMN IF NOT EXISTS approved TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS approved_by INT NULL,
ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS serial_number INT NULL;

ALTER TABLE patients
ADD COLUMN IF NOT EXISTS patient_number VARCHAR(50) UNIQUE;

CREATE INDEX IF NOT EXISTS idx_appointment_number ON appointments(appointment_number);
CREATE INDEX IF NOT EXISTS idx_patient_number ON patients(patient_number);
