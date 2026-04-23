<?php
/**
 * Emergency One-Click Restore Script
 * 
 * Usage from command line:
 *   php restore.php                     (restores from latest database_backup.sql)
 *   php restore.php backup_20251124.sql (restores from specific backup file)
 * 
 * CAUTION: This script will DROP and RECREATE the database.
 * Backup your current database first if you want to save it!
 */

// Colors for terminal output
$colors = [
    'reset'   => "\033[0m",
    'red'     => "\033[31m",
    'green'   => "\033[32m",
    'yellow'  => "\033[33m",
    'blue'    => "\033[34m",
    'cyan'    => "\033[36m",
];

function color($text, $code) {
    global $colors;
    return $colors[$code] . $text . $colors['reset'];
}

function log_info($msg) {
    echo color("[INFO]", 'blue') . " " . $msg . PHP_EOL;
}

function log_success($msg) {
    echo color("[SUCCESS]", 'green') . " " . $msg . PHP_EOL;
}

function log_warning($msg) {
    echo color("[WARNING]", 'yellow') . " " . $msg . PHP_EOL;
}

function log_error($msg) {
    echo color("[ERROR]", 'red') . " " . $msg . PHP_EOL;
}

// ============================================================================
// MAIN RESTORE LOGIC
// ============================================================================

echo PHP_EOL;
echo color("╔══════════════════════════════════════════════════════════════╗", 'cyan') . PHP_EOL;
echo color("║  CLINICAPP EMERGENCY DATABASE RESTORE                       ║", 'cyan') . PHP_EOL;
echo color("║  Use this script to recover from database corruption        ║", 'cyan') . PHP_EOL;
echo color("╚══════════════════════════════════════════════════════════════╝", 'cyan') . PHP_EOL;
echo PHP_EOL;

// Determine which backup to restore
$backup_file = null;

if (isset($argv[1]) && !empty($argv[1])) {
    // User specified a backup file
    $backup_file = $argv[1];
    if (!file_exists($backup_file)) {
        $backup_file = __DIR__ . '/archive/' . $argv[1];
    }
} else {
    // Find the most recent backup
    $archive_dir = __DIR__ . '/archive/';
    $backups = glob($archive_dir . 'backup_*.sql');
    if (empty($backups)) {
        $backups = glob($archive_dir . 'database_backup.sql');
    }
    
    if (empty($backups)) {
        log_error("No backup files found in $archive_dir");
        exit(1);
    }
    
    usort($backups, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $backup_file = $backups[0];
}

// Verify backup file exists and is readable
if (!file_exists($backup_file)) {
    log_error("Backup file not found: $backup_file");
    exit(1);
}

if (!is_readable($backup_file)) {
    log_error("Backup file is not readable: $backup_file");
    exit(1);
}

$file_size_mb = number_format(filesize($backup_file) / (1024 * 1024), 2);
log_info("Using backup file: " . basename($backup_file));
log_info("File size: ${file_size_mb} MB");
log_info("Last modified: " . date('Y-m-d H:i:s', filemtime($backup_file)));

echo PHP_EOL;
log_warning("CAUTION: This will DROP and RECREATE the clinic_management database.");
log_warning("All current data will be replaced with the backup.");
echo PHP_EOL;

// Prompt for confirmation
echo color("Type 'RESTORE' (all caps) to proceed, or press Ctrl+C to cancel: ", 'yellow');
$response = trim(fgets(STDIN));

if ($response !== 'RESTORE') {
    log_warning("Restore cancelled.");
    exit(0);
}

echo PHP_EOL;

// Load config and connect to database
try {
    require_once __DIR__ . '/config/config.php';
} catch (Exception $e) {
    log_error("Could not load config: " . $e->getMessage());
    exit(1);
}

if (!($db instanceof PDO)) {
    log_error("Database connection failed. Check config/config.php");
    exit(1);
}

log_success("Connected to MySQL.");

// ============================================================================
// SAVE CURRENT DATABASE (OPTIONAL FORENSICS BACKUP)
// ============================================================================

$timestamp = date('YmdHis');
$forensics_file = __DIR__ . '/archive/forensics_' . $timestamp . '.sql';

log_info("Saving current database to forensics backup...");
try {
    $cmd = 'C:\\xampp\\mysql\\bin\\mysqldump.exe -u root clinic_management > ' . escapeshellarg($forensics_file) . ' 2>nul';
    $output = [];
    $return_var = 0;
    exec($cmd, $output, $return_var);
    
    if ($return_var === 0 && file_exists($forensics_file)) {
        $fsize = number_format(filesize($forensics_file) / (1024 * 1024), 2);
        log_success("Forensics backup saved: forensics_${timestamp}.sql (${fsize} MB)");
    } else {
        log_warning("Could not save forensics backup (this is OK, proceeding anyway)");
    }
} catch (Exception $e) {
    log_warning("Forensics backup failed (proceeding anyway): " . $e->getMessage());
}

echo PHP_EOL;

// ============================================================================
// DROP AND RECREATE DATABASE
// ============================================================================

log_info("Dropping current database...");
try {
    $db->exec("DROP DATABASE IF EXISTS clinic_management");
    log_success("Database dropped.");
} catch (Exception $e) {
    log_error("Failed to drop database: " . $e->getMessage());
    exit(1);
}

log_info("Creating fresh database...");
try {
    $db->exec("CREATE DATABASE clinic_management");
    log_success("Database created.");
} catch (Exception $e) {
    log_error("Failed to create database: " . $e->getMessage());
    exit(1);
}

// ============================================================================
// RESTORE BACKUP
// ============================================================================

log_info("Restoring backup (this may take a minute or two)...");
echo PHP_EOL;

$sql_content = file_get_contents($backup_file);
if ($sql_content === false) {
    log_error("Could not read backup file");
    exit(1);
}

// Split SQL by semicolon and execute statements (handle comments, etc.)
$statements = preg_split('/;(?=\s*$)/m', $sql_content);
$count = 0;
$errors = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement) || preg_match('/^--/', $statement)) {
        continue; // Skip empty or comment-only lines
    }
    
    try {
        $db->exec($statement);
        $count++;
        
        // Progress indicator every 10 statements
        if ($count % 10 === 0) {
            echo ".";
            flush();
        }
    } catch (Exception $e) {
        $errors++;
        log_warning("Statement failed (continuing): " . substr($e->getMessage(), 0, 80));
    }
}

echo PHP_EOL;
log_success("Restored $count SQL statements (${errors} non-critical errors).");

// ============================================================================
// VALIDATE RESTORE
// ============================================================================

echo PHP_EOL;
log_info("Validating restored data...");
echo PHP_EOL;

$tables_to_check = ['users', 'patients', 'doctors', 'appointments'];
foreach ($tables_to_check as $table) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM $table");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['cnt'];
        echo "  " . color("✓", 'green') . " $table: $count records" . PHP_EOL;
    } catch (Exception $e) {
        echo "  " . color("✗", 'red') . " $table: error or missing" . PHP_EOL;
    }
}

// ============================================================================
// SUMMARY
// ============================================================================

echo PHP_EOL;
echo color("╔══════════════════════════════════════════════════════════════╗", 'cyan') . PHP_EOL;
echo color("║  RESTORE COMPLETE                                           ║", 'cyan') . PHP_EOL;
echo color("╚══════════════════════════════════════════════════════════════╝", 'cyan') . PHP_EOL;
echo PHP_EOL;

log_success("Database has been restored from: " . basename($backup_file));
log_info("Restart Apache and MySQL if they are stopped.");
log_info("Visit http://localhost/clinicapp/ to verify.");

echo PHP_EOL;
log_warning("If you see errors, check:");
log_warning("  - logs/errors.log for application errors");
log_warning("  - C:\\xampp\\mysql\\data\\*.err for MySQL errors");

echo PHP_EOL;

?>
