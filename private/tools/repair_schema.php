<?php
/**
 * Safe Schema Repair Script
 * - Adds commonly-missing columns/tables if they do not exist
 * - Designed to be idempotent and safe for local/dev environments
 * Run from project root: php tools/repair_schema.php
 */

require_once __DIR__ . '/../config/config.php';

function existsTable($db, $table) {
    $q = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl");
    $q->bindParam(':tbl', $table);
    $q->execute();
    $r = $q->fetch(PDO::FETCH_ASSOC);
    return intval($r['cnt']) > 0;
}

function existsColumn($db, $table, $column) {
    $q = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col");
    $q->bindParam(':tbl', $table);
    $q->bindParam(':col', $column);
    $q->execute();
    $r = $q->fetch(PDO::FETCH_ASSOC);
    return intval($r['cnt']) > 0;
}

$steps = [];

try {
    if (!($db instanceof PDO)) {
        throw new Exception('Database connection is not available. Aborting.');
    }

    // 1) patients.allergies (example missing column)
    if (!existsColumn($db, 'patients', 'allergies')) {
        $sql = "ALTER TABLE patients ADD COLUMN allergies TEXT NULL AFTER medical_history";
        $db->exec($sql);
        $steps[] = 'Added column patients.allergies (TEXT NULL)';
    } else {
        $steps[] = 'patients.allergies exists';
    }

    // 2) appointments.symptoms
    if (!existsColumn($db, 'appointments', 'symptoms')) {
        $sql = "ALTER TABLE appointments ADD COLUMN symptoms TEXT NULL AFTER consultation_type";
        $db->exec($sql);
        $steps[] = 'Added column appointments.symptoms (TEXT NULL)';
    } else {
        $steps[] = 'appointments.symptoms exists';
    }

    // 3) appointments.appointment_serial
    if (!existsColumn($db, 'appointments', 'appointment_serial')) {
        $sql = "ALTER TABLE appointments ADD COLUMN appointment_serial INT NULL AFTER appointment_time";
        $db->exec($sql);
        $steps[] = 'Added column appointments.appointment_serial (INT NULL)';
    } else {
        $steps[] = 'appointments.appointment_serial exists';
    }

    // 4) users.doctor_id
    if (!existsColumn($db, 'users', 'doctor_id')) {
        $sql = "ALTER TABLE users ADD COLUMN doctor_id INT NULL AFTER role";
        $db->exec($sql);
        $steps[] = 'Added column users.doctor_id (INT NULL)';
    } else {
        $steps[] = 'users.doctor_id exists';
    }

    // 5) appointment_counters table
    if (!existsTable($db, 'appointment_counters')) {
        $sql = "CREATE TABLE appointment_counters (
            `date` DATE NOT NULL PRIMARY KEY,
            last_serial INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        $steps[] = 'Created table appointment_counters';
    } else {
        $steps[] = 'appointment_counters exists';
    }

    // 6) password_reset_tokens table (ensure columns)
    if (!existsTable($db, 'password_reset_tokens')) {
        $sql = "CREATE TABLE password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        $steps[] = 'Created table password_reset_tokens';
    } else {
        $steps[] = 'password_reset_tokens exists';
    }

    // 7) doctors.email (ensure column exists)
    if (!existsColumn($db, 'doctors', 'email')) {
        $sql = "ALTER TABLE doctors ADD COLUMN email VARCHAR(255) NULL AFTER specialization";
        $db->exec($sql);
        $steps[] = 'Added column doctors.email (VARCHAR(255) NULL)';
    } else {
        $steps[] = 'doctors.email exists';
    }

    // 8) patients.date_of_birth ensure column exists
    if (!existsColumn($db, 'patients', 'date_of_birth')) {
        $sql = "ALTER TABLE patients ADD COLUMN date_of_birth DATE NULL AFTER address";
        $db->exec($sql);
        $steps[] = 'Added column patients.date_of_birth (DATE NULL)';
    } else {
        $steps[] = 'patients.date_of_birth exists';
    }

    // 9) ensure appointments.status default exists
    if (!existsColumn($db, 'appointments', 'status')) {
        $sql = "ALTER TABLE appointments ADD COLUMN status VARCHAR(32) DEFAULT 'scheduled'";
        $db->exec($sql);
        $steps[] = 'Added column appointments.status (VARCHAR 32 DEFAULT scheduled)';
    } else {
        $steps[] = 'appointments.status exists';
    }

    // Write a simple report to process.log
    $report = "[".date('Y-m-d H:i:s')."] [SCHEMA_REPAIR] Schema repair run completed:\n" . implode("\n", $steps) . "\n";
    file_put_contents(__DIR__ . '/../logs/process.log', $report, FILE_APPEND | LOCK_EX);

    echo "Schema repair completed. See logs/process.log for details.\n";

} catch (Exception $e) {
    $err = "[".date('Y-m-d H:i:s')."] [SCHEMA_REPAIR_ERROR] " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../logs/errors.log', $err, FILE_APPEND | LOCK_EX);
    echo "Schema repair failed: " . $e->getMessage() . "\n";
}

?>