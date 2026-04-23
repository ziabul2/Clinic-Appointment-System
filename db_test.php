<?php
// Simple database connection test.
require_once __DIR__ . '/config/config.php';

if ($db) {
    echo "Database connection OK.\n";
    try {
        $stmt = $db->query('SELECT NOW() as now');
        $row = $stmt->fetch();
        echo "Server time: " . ($row['now'] ?? 'unknown') . "\n";
    } catch (Exception $e) {
        echo "Query failed: " . $e->getMessage();
    }
} else {
    echo "Database connection FAILED. Check logs in /logs/errors.log and /logs/process.log\n";
}

?>
