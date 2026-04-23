<?php
// backup_db.php
// Usage: run daily via Task Scheduler. Creates SQL dump files in backups/ directory.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$backup_dir = __DIR__ . '/backups/';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);

$dbHost = 'localhost';
$dbName = 'clinic_management';
$dbUser = 'root';
$dbPass = '';

// try to read from database.php class if present
try {
    $dbConf = new Database();
    // reflect properties if accessible - otherwise use defaults above
} catch (Exception $e) {
    // ignore
}

// mysqldump path for XAMPP
$mysqldump = 'C:\\\\xampp\\\\mysql\\\\bin\\\\mysqldump.exe';
if (!file_exists($mysqldump)) {
    // fall back to common path
    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
}

$timestamp = date('Ymd_His');
$filename = $backup_dir . "backup_{$dbName}_{$timestamp}.sql";

if (file_exists($mysqldump)) {
    $cmd = sprintf('"%s" -h %s -u %s %s > "%s"', $mysqldump, escapeshellarg($dbHost), escapeshellarg($dbUser), escapeshellarg($dbName), $filename);
    // If password is set, include it (beware of command-line exposure)
    if (!empty($dbPass)) {
        // On Windows mysqldump -pPASSWORD (no space)
        $cmd = sprintf('"%s" -h %s -u %s -p%s %s > "%s"', $mysqldump, escapeshellarg($dbHost), escapeshellarg($dbUser), $dbPass, escapeshellarg($dbName), $filename);
    }

    exec($cmd, $output, $ret);
    if ($ret === 0) {
        logAction('DB_BACKUP', "Backup created: $filename");
        echo "Backup created: $filename\n";
    } else {
        logAction('DB_BACKUP_ERROR', "Backup command failed: $cmd");
        echo "Backup failed. Check logs.\n";
    }
} else {
    logAction('DB_BACKUP_ERROR', 'mysqldump not found: ' . $mysqldump);
    echo "mysqldump not found on the server (expected at: $mysqldump).\n";
}

?>