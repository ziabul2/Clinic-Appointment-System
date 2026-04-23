<?php
// Ensure users.doctor_id column exists and optionally add FK to doctors
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/config.php';

if (!defined('DB_OK') || DB_OK === false) {
    echo "Database not available.\n";
    exit(2);
}

try {
    $q = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'doctor_id'");
    $q->execute(); $r = $q->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) > 0) {
        echo "users.doctor_id already exists.\n";
        exit(0);
    }

    echo "Adding doctor_id column to users...\n";
    $db->exec("ALTER TABLE users ADD COLUMN doctor_id INT NULL AFTER role");
    echo "Added column.\n";

    // If doctors table exists, try adding FK
    $t = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors'");
    $t->execute(); $tr = $t->fetch(PDO::FETCH_ASSOC);
    if (intval($tr['cnt']) > 0) {
        try {
            $db->exec("ALTER TABLE users ADD CONSTRAINT fk_users_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL ON UPDATE CASCADE");
            echo "Added foreign key fk_users_doctor.\n";
        } catch (Exception $e) {
            echo "Could not add FK: " . $e->getMessage() . "\n";
        }
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(3);
}
