<?php
// Apply small schema fixes needed by the app: add missing columns and helper tables.
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/config.php';

if (!defined('DB_OK') || DB_OK === false) {
    echo "Database not available.\n";
    exit(2);
}

function colExists($db, $table, $col) {
    $q = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
    $q->execute([':t' => $table, ':c' => $col]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    return intval($r['cnt']) > 0;
}

function tableExists($db, $table) {
    $q = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $q->execute([':t' => $table]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    return intval($r['cnt']) > 0;
}

$actions = [];

// appointments.appointment_serial INT NULL
if (!colExists($db, 'appointments', 'appointment_serial')) {
    try {
        $db->exec("ALTER TABLE appointments ADD COLUMN appointment_serial INT NULL AFTER appointment_time");
        $actions[] = "Added appointments.appointment_serial";
    } catch (Exception $e) {
        $actions[] = "Failed to add appointment_serial: " . $e->getMessage();
    }
} else { $actions[] = "appointments.appointment_serial exists"; }

// appointments.symptoms TEXT NULL
if (!colExists($db, 'appointments', 'symptoms')) {
    try {
        $db->exec("ALTER TABLE appointments ADD COLUMN symptoms TEXT NULL AFTER notes");
        $actions[] = "Added appointments.symptoms";
    } catch (Exception $e) {
        $actions[] = "Failed to add symptoms: " . $e->getMessage();
    }
} else { $actions[] = "appointments.symptoms exists"; }

// patients.allergies TEXT NULL
if (!colExists($db, 'patients', 'allergies')) {
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN allergies TEXT NULL AFTER medical_history");
        $actions[] = "Added patients.allergies";
    } catch (Exception $e) {
        $actions[] = "Failed to add patients.allergies: " . $e->getMessage();
    }
} else { $actions[] = "patients.allergies exists"; }

// appointment_counters table
if (!tableExists($db, 'appointment_counters')) {
    try {
        $sql = "CREATE TABLE appointment_counters (
            `date` DATE NOT NULL PRIMARY KEY,
            last_serial INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        $actions[] = "Created appointment_counters table";
    } catch (Exception $e) {
        $actions[] = "Failed to create appointment_counters: " . $e->getMessage();
    }
} else { $actions[] = "appointment_counters exists"; }

// password_reset_tokens table (use migration if missing)
if (!tableExists($db, 'password_reset_tokens')) {
    try {
        $sql = "CREATE TABLE password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        // try adding FK if users table exists
        if (tableExists($db, 'users')) {
            try {
                $db->exec("ALTER TABLE password_reset_tokens ADD CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE");
            } catch (Exception $e) {
                // ignore fk errors
            }
        }
        $actions[] = "Created password_reset_tokens table";
    } catch (Exception $e) {
        $actions[] = "Failed to create password_reset_tokens: " . $e->getMessage();
    }
} else { $actions[] = "password_reset_tokens exists"; }

echo "Schema fix actions:\n";
foreach ($actions as $a) echo " - $a\n";

echo "Done.\n";
exit(0);
