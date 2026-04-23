<?php
/**
 * Enhanced Database Backup Script
 * 
 * Exports clinic_management database to individual table SQL files.
 * This script is designed to be called from backup automation (PowerShell scripts).
 * 
 * Usage:
 *   php db_backup_enhanced.php
 * 
 * Output: Creates .sql files in the same directory for each database entity
 * - database_create.sql (database creation statement)
 * - table_*.sql (one file per table)
 * - extra.sql (views, triggers, stored procedures if any)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'clinic_management';
$output_dir = __DIR__; // Current directory
$mysqldump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe'; // Full path to mysqldump

// Try to create PDO connection for metadata queries
try {
    $pdo = new PDO(
        "mysql:host=$host",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    fwrite(STDERR, "ERROR: Could not connect to MySQL: " . $e->getMessage() . "\n");
    exit(2);
}

// ============================================================================
// 1. Export Database Creation Statement
// ============================================================================

$db_create_file = $output_dir . '/' . $database . '_database.sql';
$cmd = escapeshellcmd($mysqldump_path) . ' ' .
    '--user=' . escapeshellarg($username) .
    ' --password=' . escapeshellarg($password) .
    ' --host=' . escapeshellarg($host) .
    ' --no-data --create-options ' .
    $database .
    ' --skip-dump-date ' .
    '> ' . escapeshellarg($db_create_file);

$result = system($cmd, $exit_code);
if ($exit_code !== 0) {
    fwrite(STDERR, "WARNING: Database creation export returned exit code $exit_code\n");
}
echo "Exported database schema: $db_create_file\n";

// ============================================================================
// 2. Get list of all tables
// ============================================================================

$stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?");
$stmt->execute([$database]);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    fwrite(STDERR, "ERROR: No tables found in database '$database'\n");
    exit(3);
}

echo "Found " . count($tables) . " tables\n";

// ============================================================================
// 3. Export Each Table
// ============================================================================

foreach ($tables as $table) {
    $sanitized_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $table);
    $table_file = $output_dir . '/' . $database . '_table_' . $sanitized_name . '.sql';
    
    $cmd = escapeshellcmd($mysqldump_path) . ' ' .
        '--user=' . escapeshellarg($username) .
        ' --password=' . escapeshellarg($password) .
        ' --host=' . escapeshellarg($host) .
        ' --skip-dump-date ' .
        $database . ' ' . escapeshellarg($table) .
        ' > ' . escapeshellarg($table_file);
    
    $result = system($cmd, $exit_code);
    if ($exit_code !== 0) {
        fwrite(STDERR, "WARNING: Export of table '$table' returned exit code $exit_code\n");
    } else {
        $file_size = filesize($table_file);
        echo "Exported table '$table': $table_file (" . number_format($file_size, 0) . " bytes)\n";
    }
}

// ============================================================================
// 4. Check for Views, Triggers, Stored Procedures
// ============================================================================

$stmt_views = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = ?");
$stmt_views->execute([$database]);
$view_count = (int)$stmt_views->fetchColumn();

// Export views, triggers, procedures if they exist
if ($view_count > 0 || $database === 'clinic_management') {
    $extra_file = $output_dir . '/' . $database . '_extra.sql';
    $cmd = escapeshellcmd($mysqldump_path) . ' ' .
        '--user=' . escapeshellarg($username) .
        ' --password=' . escapeshellarg($password) .
        ' --host=' . escapeshellarg($host) .
        ' --skip-dump-date ' .
        ' --no-data --triggers --routines ' .
        $database .
        ' > ' . escapeshellarg($extra_file);
    
    $result = system($cmd, $exit_code);
    if ($exit_code === 0 && filesize($extra_file) > 100) {
        $file_size = filesize($extra_file);
        echo "Exported views/triggers/routines: $extra_file (" . number_format($file_size, 0) . " bytes)\n";
    }
}

echo "\n✓ Database backup export completed successfully.\n";
echo "Output directory: $output_dir\n";
echo "Database: $database\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

$pdo = null;
exit(0);
