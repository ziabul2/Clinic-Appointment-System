-- clinic_database.sql
CREATE DATABASE IF NOT EXISTS clinic_management;
USE clinic_management;

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    emergency_contact VARCHAR(15),
    medical_history TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctors table
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    specialization VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15),
    license_number VARCHAR(50) UNIQUE,
    available_days SET('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
    consultation_fee DECIMAL(8,2),
    available_time_start TIME,
    available_time_end TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no-show') DEFAULT 'scheduled',
    notes TEXT,
    consultation_type ENUM('general', 'follow-up', 'emergency', 'routine') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_timeslot (doctor_id, appointment_date, appointment_time)
);

-- Users table for system access (optional)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'receptionist', 'doctor') DEFAULT 'receptionist',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO doctors (first_name, last_name, specialization, email, phone, license_number, available_days, consultation_fee, available_time_start, available_time_end) VALUES
('Dr. Sarah', 'Wilson', 'Cardiology', 'sarah.wilson@clinic.com', '111-222-3333', 'MED12345', 'Monday,Wednesday,Friday', 50.00, '09:00:00', '17:00:00'),
('Dr. David', 'Brown', 'Dermatology', 'david.brown@clinic.com', '111-222-4444', 'MED12346', 'Tuesday,Thursday,Saturday', 45.00, '10:00:00', '16:00:00'),
('Dr. Emily', 'Davis', 'Pediatrics', 'emily.davis@clinic.com', '111-222-5555', 'MED12347', 'Monday,Tuesday,Wednesday,Thursday,Friday', 40.00, '08:00:00', '15:00:00');

INSERT INTO patients (first_name, last_name, email, phone, address, date_of_birth, gender, emergency_contact) VALUES
('John', 'Doe', 'john.doe@email.com', '123-456-7890', '123 Main St, City, State', '1985-05-15', 'Male', '123-456-7891'),
('Jane', 'Smith', 'jane.smith@email.com', '123-456-7891', '456 Oak Ave, City, State', '1990-08-22', 'Female', '123-456-7892'),
('Mike', 'Johnson', 'mike.johnson@email.com', '123-456-7892', '789 Pine Rd, City, State', '1978-12-10', 'Male', '123-456-7893');

INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, notes, consultation_type) VALUES
(1, 1, '2024-01-15', '09:00:00', 'scheduled', 'Regular heart checkup', 'routine'),
(2, 2, '2024-01-16', '10:30:00', 'scheduled', 'Skin allergy consultation', 'general'),
(3, 3, '2024-01-17', '14:00:00', 'completed', 'Child vaccination', 'routine');

INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@clinic.com', 'admin'),
('reception', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception@clinic.com', 'receptionist');