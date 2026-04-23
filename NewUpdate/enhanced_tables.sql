-- Enhanced Clinic Management System Tables

-- Medical Records Table
CREATE TABLE medical_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    diagnosis TEXT,
    symptoms TEXT,
    temperature DECIMAL(4,2),
    blood_pressure VARCHAR(20),
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    notes TEXT,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
);

-- Prescriptions Table
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    record_id INT NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES medical_records(record_id)
);

-- Billing Table
CREATE TABLE billing (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    appointment_id INT,
    consultation_fee DECIMAL(10,2),
    medicine_charges DECIMAL(10,2),
    test_charges DECIMAL(10,2),
    other_charges DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    paid_amount DECIMAL(10,2),
    payment_status ENUM('pending', 'paid', 'partial') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
);

-- Email Templates Table
CREATE TABLE email_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    variables TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    user_type ENUM('patient', 'doctor', 'receptionist', 'admin'),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'prescription', 'billing', 'system'),
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings Table
CREATE TABLE clinic_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_name VARCHAR(255) NOT NULL,
    clinic_address TEXT,
    clinic_phone VARCHAR(20),
    clinic_email VARCHAR(100),
    clinic_logo VARCHAR(255),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    smtp_host VARCHAR(255),
    smtp_port INT,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    smtp_encryption VARCHAR(10),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO clinic_settings (clinic_name, clinic_address, clinic_phone, clinic_email) 
VALUES ('Your Clinic Name', 'Your Clinic Address', 'Your Phone', 'your@email.com');

-- Insert email templates
INSERT INTO email_templates (template_name, subject, content, variables) VALUES
('appointment_confirmation', 'Appointment Confirmation - [CLINIC_NAME]',
'Dear [PATIENT_NAME],\n\nYour appointment has been confirmed.\n\nAppointment Details:\nDate: [APPOINTMENT_DATE]\nTime: [APPOINTMENT_TIME]\nDoctor: [DOCTOR_NAME]\n\nThank you for choosing [CLINIC_NAME].\n\nBest regards,\n[CLINIC_NAME] Team',
'[PATIENT_NAME], [APPOINTMENT_DATE], [APPOINTMENT_TIME], [DOCTOR_NAME], [CLINIC_NAME]'),

('appointment_reminder', 'Appointment Reminder - [CLINIC_NAME]',
'Dear [PATIENT_NAME],\n\nThis is a reminder for your upcoming appointment.\n\nAppointment Details:\nDate: [APPOINTMENT_DATE]\nTime: [APPOINTMENT_TIME]\nDoctor: [DOCTOR_NAME]\n\nPlease arrive 15 minutes early.\n\nBest regards,\n[CLINIC_NAME] Team',
'[PATIENT_NAME], [APPOINTMENT_DATE], [APPOINTMENT_TIME], [DOCTOR_NAME], [CLINIC_NAME]'),

('prescription_ready', 'Your Prescription is Ready - [CLINIC_NAME]',
'Dear [PATIENT_NAME],\n\nYour prescription from Dr. [DOCTOR_NAME] is now ready.\n\nYou can collect it from the clinic or view it in your patient portal.\n\nBest regards,\n[CLINIC_NAME] Team',
'[PATIENT_NAME], [DOCTOR_NAME], [CLINIC_NAME]');