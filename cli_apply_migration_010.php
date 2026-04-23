<?php
require_once 'config/config.php';
$sql = file_get_contents(__DIR__ . '/migrations/010_create_notifications_table.sql');
try {
    $db->exec($sql);
    echo "Migration applied: notifications table created (or already exists)\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
