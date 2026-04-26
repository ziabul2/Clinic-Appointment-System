<?php
// Inspect DB schema: list tables and columns
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/config.php';

if (!defined('DB_OK') || DB_OK === false) {
    echo "Database not available.\n";
    exit(2);
}

$tables = [];
$q = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $colQ = $db->prepare("SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $colQ->execute([':t' => $t]);
    $cols = $colQ->fetchAll(PDO::FETCH_ASSOC);
    $tables[$t] = $cols;
}

foreach ($tables as $tn => $cols) {
    echo "Table: $tn\n";
    foreach ($cols as $c) echo "  - {$c['COLUMN_NAME']} ({$c['COLUMN_TYPE']})\n";
    echo "\n";
}
