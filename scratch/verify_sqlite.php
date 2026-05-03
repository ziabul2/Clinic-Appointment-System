<?php
/**
 * SQLite Database Verification Script
 * Checks for table integrity and record counts.
 */
require_once __DIR__ . '/../config/config.php';

$sqliteFile = __DIR__ . '/../DatabaseSQL/clinic_offline.db';
if (!file_exists($sqliteFile)) {
    die("Error: SQLite database file not found at $sqliteFile\n");
}

try {
    $sqlite = new PDO("sqlite:" . $sqliteFile);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "SQLite Database Verification\n";
    echo "============================\n";
    echo "File: $sqliteFile\n";
    echo "Size: " . round(filesize($sqliteFile) / 1024 / 1024, 2) . " MB\n\n";

    $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

    echo str_pad("Table Name", 30) . " | " . "Record Count\n";
    echo str_repeat("-", 45) . "\n";

    foreach ($tables as $table) {
        $count = $sqlite->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo str_pad($table, 30) . " | " . $count . "\n";
    }

    echo "\nIntegrity Check: ";
    $check = $sqlite->query("PRAGMA integrity_check")->fetchColumn();
    echo $check . "\n";

    if ($check === 'ok') {
        echo "\nSUCCESS: SQLite database is healthy and populated.\n";
    } else {
        echo "\nWARNING: SQLite database integrity check failed!\n";
    }

} catch (Exception $e) {
    echo "Error during verification: " . $e->getMessage() . "\n";
}
