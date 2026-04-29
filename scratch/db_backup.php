<?php
require_once __DIR__ . '/../config/config.php';

$backupDir = __DIR__ . '/../sqls_DB';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$sqlFile = $backupDir . '/clinic_management.sql';
$jsonFile = $backupDir . '/clinic_management.json';

echo "Starting Backup Process...\n";

// 1. Export SQL using mysqldump
// Assuming standard XAMPP paths for mysqldump
$mysqlPath = 'C:\xampp\mysql\bin\mysqldump.exe';
if (!file_exists($mysqlPath)) {
    // Try generic command
    $command = "mysqldump -u root clinic_management > \"$sqlFile\"";
} else {
    $command = "\"$mysqlPath\" -u root clinic_management > \"$sqlFile\"";
}

system($command, $sqlRet);
if ($sqlRet === 0) {
    echo "SQL Backup Completed: clinic_management.sql\n";
} else {
    echo "SQL Backup Failed with code: $sqlRet\n";
}

// 2. Export JSON (Human Readable)
try {
    $tablesStmt = $db->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $fullData = [];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT * FROM `$table` ");
        $fullData[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Exporting Table: $table (" . count($fullData[$table]) . " rows)\n";
    }
    
    file_put_contents($jsonFile, json_encode($fullData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "JSON Backup Completed: clinic_management.json\n";
} catch (Exception $e) {
    echo "JSON Backup Failed: " . $e->getMessage() . "\n";
}

echo "\nBackup Finished Successfully.\n";
