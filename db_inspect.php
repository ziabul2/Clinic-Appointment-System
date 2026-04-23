<?php
require_once __DIR__ . '/config/config.php';

header_remove();
// CLI-friendly output
echo "DB inspection for 'users' table:\n";
try {
    $q = $db->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'");
    $q->execute();
    $cols = $q->fetchAll(PDO::FETCH_ASSOC);
    if (empty($cols)) {
        echo "No columns found or table 'users' does not exist.\n";
    } else {
        foreach ($cols as $c) {
            echo sprintf("- %s : %s | nullable=%s | default=%s\n", $c['COLUMN_NAME'], $c['COLUMN_TYPE'], $c['IS_NULLABLE'], $c['COLUMN_DEFAULT']);
        }
    }

    // Show sample users
    echo "\nSample users (first 10):\n";
    $s = $db->prepare("SELECT user_id, username, email, role, doctor_id, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $s->execute();
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "(no users found)\n";
    } else {
        foreach ($rows as $r) {
            echo sprintf("#%s %s <%s> role=%s doctor_id=%s created=%s\n", $r['user_id'], $r['username'], $r['email'], $r['role'], $r['doctor_id'] ?? 'NULL', $r['created_at']);
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>