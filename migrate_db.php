<?php
// migrate_db.php - Safe migration helper for common missing columns/tables
// Run this from CLI: C:\xampp\php\php.exe migrate_db.php
require_once __DIR__ . '/config/config.php';

// CLI-friendly
function out($s) { echo $s . PHP_EOL; }

try {
    out('Starting migration checks...');

    // 1) Ensure users.doctor_id exists
    $col = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'doctor_id'");
    $col->execute(); $r = $col->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        out("Adding column users.doctor_id...");
        $db->exec("ALTER TABLE users ADD COLUMN doctor_id INT NULL AFTER role");
        // try FK if doctors exists
        $tbl = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors'");
        $tbl->execute(); $t = $tbl->fetch(PDO::FETCH_ASSOC);
        if (intval($t['cnt']) > 0) {
            try {
                $db->exec("ALTER TABLE users ADD CONSTRAINT fk_users_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL ON UPDATE CASCADE");
                out('Added FK fk_users_doctor');
            } catch (Exception $e) {
                out('Could not add FK fk_users_doctor: ' . $e->getMessage());
            }
        }
    } else {
        out('users.doctor_id already exists');
    }

    // 2) Ensure doctors.profile_picture exists
    $col = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'profile_picture'");
    $col->execute(); $r = $col->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        out('Adding doctors.profile_picture column...');
        $db->exec("ALTER TABLE doctors ADD COLUMN profile_picture VARCHAR(255) NULL AFTER available_time_end");
    } else {
        out('doctors.profile_picture already exists');
    }

    // 3) Ensure patients.allergies exists
    $col = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'allergies'");
    $col->execute(); $r = $col->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        out('Adding patients.allergies column...');
        $db->exec("ALTER TABLE patients ADD COLUMN allergies TEXT NULL AFTER medical_history");
    } else {
        out('patients.allergies already exists');
    }

    // 4) Ensure appointments.symptoms exists
    $col = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'symptoms'");
    $col->execute(); $r = $col->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        out('Adding appointments.symptoms column...');
        $db->exec("ALTER TABLE appointments ADD COLUMN symptoms TEXT NULL AFTER consultation_type");
    } else {
        out('appointments.symptoms already exists');
    }

    // 4b) Ensure appointments.appointment_serial exists
    $col = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'appointment_serial'");
    $col->execute(); $r = $col->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        out('Adding appointments.appointment_serial column...');
        $db->exec("ALTER TABLE appointments ADD COLUMN appointment_serial INT NULL AFTER appointment_time");
    } else {
        out('appointments.appointment_serial already exists');
    }

    // 5) Ensure password_reset_tokens table exists
    $tbl = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'password_reset_tokens'");
    $tbl->execute(); $t = $tbl->fetch(PDO::FETCH_ASSOC);
    if (intval($t['cnt']) === 0) {
        out('Creating password_reset_tokens table...');
        $sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
    } else {
        out('password_reset_tokens already exists');
    }

    // 5b) Ensure appointment_counters table exists (for per-day serial allocation)
    $tbl = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointment_counters'");
    $tbl->execute(); $t = $tbl->fetch(PDO::FETCH_ASSOC);
    if (intval($t['cnt']) === 0) {
        out('Creating appointment_counters table...');
        $sql = "CREATE TABLE IF NOT EXISTS appointment_counters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `date` DATE NOT NULL,
            last_serial INT NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
    } else {
        out('appointment_counters already exists');
    }

    // 6) Ensure waiting_list table exists
    $tbl = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'waiting_list'");
    $tbl->execute(); $t = $tbl->fetch(PDO::FETCH_ASSOC);
    if (intval($t['cnt']) === 0) {
        out('Creating waiting_list table...');
        $sql = "CREATE TABLE IF NOT EXISTS waiting_list (
            waiting_id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            user_id INT NULL,
            status ENUM('waiting','taken','processed','cancelled') DEFAULT 'waiting',
            requested_at DATETIME NOT NULL,
            taken_by INT NULL,
            appointment_id INT NULL,
            token VARCHAR(128) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (patient_id),
            INDEX (status),
            INDEX (requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
    } else {
        out('waiting_list already exists');
    }

    // 6) Ensure users.role enum contains 'patient'
    try {
        $col = $db->prepare("SHOW COLUMNS FROM users LIKE 'role'");
        $col->execute(); $roleCol = $col->fetch(PDO::FETCH_ASSOC);
        if ($roleCol && isset($roleCol['Type'])) {
            $type = $roleCol['Type']; // e.g. enum('admin','receptionist','doctor')
            if (strpos($type, "'patient'") === false) {
                out("Adding 'patient' to users.role enum...");
                // Modify the enum to include patient safely (preserve default as 'receptionist')
                $db->exec("ALTER TABLE users MODIFY `role` ENUM('admin','receptionist','doctor','patient') NULL DEFAULT 'receptionist'");
                out("users.role enum updated to include 'patient'");
            } else {
                out("users.role already contains 'patient'");
            }
        } else {
            out('Could not read users.role column info');
        }
    } catch (Exception $e) {
        out('Could not update users.role enum: ' . $e->getMessage());
    }

    out('Migration checks complete.');
    out('Please review results above. If you want, run the CSV import via UI now (pages/users.php) or run the CLI import:');
    out('  C:\\xampp\\php\\php.exe C:\\xampp\\htdocs\\clinicapp\\import_cli.php');

} catch (Exception $e) {
    out('Migration error: ' . $e->getMessage());
}

?>