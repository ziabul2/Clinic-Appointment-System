<?php
/**
 * Migration helper to add profile columns to users table if missing.
 * Run via CLI: php tools/migrate_add_users_profile_columns.php
 */
require_once __DIR__ . '/../config/config.php';
try {
    $cols = [
        'profile_picture' => "VARCHAR(255) NULL",
        'first_name' => "VARCHAR(100) NULL",
        'last_name' => "VARCHAR(100) NULL",
        'phone' => "VARCHAR(50) NULL",
        'address' => "VARCHAR(255) NULL"
    ];
    foreach ($cols as $col => $def) {
        $q = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = :col");
        $q->bindParam(':col', $col);
        $q->execute(); $r = $q->fetch(PDO::FETCH_ASSOC);
        if (empty($r) || intval($r['cnt']) === 0) {
            echo "Adding column $col...\n";
            $db->exec("ALTER TABLE users ADD COLUMN $col $def AFTER password");
        } else {
            echo "Column $col already exists.\n";
        }
    }
    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
