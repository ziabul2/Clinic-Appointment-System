<?php
// CLI Database check for ClinicApp
// Usage: php tools/db_check.php

// Make sure errors are visible in CLI for this script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';

echo "ClinicApp DB Check - " . date('Y-m-d H:i:s') . PHP_EOL;
echo "PHP SAPI: " . PHP_SAPI . PHP_EOL;

if (!class_exists('Database')) {
    // Try to include database.php directly
    $dbfile = __DIR__ . '/../config/database.php';
    if (file_exists($dbfile)) require_once $dbfile;
}

if (!class_exists('Database')) {
    echo "ERROR: Database class not found (checked config/database.php)." . PHP_EOL;
    exit(1);
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "ERROR: Could not establish a database connection. Check logs in ./logs/errors.log or ./logs/process.log" . PHP_EOL;
    exit(2);
}

echo "OK: Connected to database." . PHP_EOL;

$checks = [];

// Basic table existence checks
$expectedTables = ['users', 'appointments', 'doctors', 'patients'];

foreach ($expectedTables as $t) {
    try {
        // Use information_schema which supports prepared statements for this check
        $stmt = $db->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = DATABASE() AND TABLE_NAME = :t");
        $stmt->execute([':t' => $t]);
        $exists = $stmt->rowCount() > 0;
        if ($exists) {
            // Count rows safely — table name is trusted from our expected list
            $cntStmt = $db->query("SELECT COUNT(*) AS c FROM `" . $t . "`");
            $count = $cntStmt->fetch(PDO::FETCH_ASSOC)['c'];
            $checks[] = [ 'table' => $t, 'exists' => true, 'rows' => (int)$count ];
        } else {
            $checks[] = [ 'table' => $t, 'exists' => false, 'rows' => null ];
        }
    } catch (Exception $e) {
        $checks[] = [ 'table' => $t, 'exists' => false, 'error' => $e->getMessage() ];
    }
}

foreach ($checks as $c) {
    if (isset($c['error'])) {
        echo "- " . $c['table'] . ": ERROR - " . $c['error'] . PHP_EOL;
    } elseif ($c['exists']) {
        echo "- " . $c['table'] . ": exists (rows=" . $c['rows'] . ")" . PHP_EOL;
    } else {
        echo "- " . $c['table'] . ": MISSING" . PHP_EOL;
    }
}

// Additional checks: sample simple queries
try {
    $v = $db->query("SELECT VERSION() AS v")->fetch(PDO::FETCH_ASSOC)['v'] ?? 'unknown';
    echo "- MySQL Version: " . $v . PHP_EOL;
} catch (Exception $e) {
    echo "- MySQL Version: ERROR - " . $e->getMessage() . PHP_EOL;
}

echo "DB check completed." . PHP_EOL;
exit(0);
