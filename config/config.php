<?php
/**
 * Main Configuration File
 * System settings and constants
 */

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development, 0 for production

// Route PHP error log to application logs
$app_error_log = __DIR__ . '/../logs/errors.log';
if (!is_dir(dirname($app_error_log))) mkdir(dirname($app_error_log), 0777, true);
ini_set('error_log', $app_error_log);

// Global exception handler to log uncaught exceptions
set_exception_handler(function($e) use ($app_error_log) {
    $msg = '['.date('Y-m-d H:i:s').'] Uncaught Exception: ' . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    error_log($msg);
    // optional: write to process.log
    $proc = __DIR__ . '/../logs/process.log';
    file_put_contents($proc, $msg, FILE_APPEND | LOCK_EX);
    if (ini_get('display_errors')) {
        echo '<pre>' . htmlspecialchars($msg) . '</pre>';
    } else {
        // Friendly message
        // echo '<div class="alert alert-danger">A system error occurred. Please contact admin.</div>';
    }
});

// Timezone setting (use valid timezone identifier)
date_default_timezone_set('Asia/Dhaka');

// Database connection (use absolute path for reliability)
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/hybrid_db.php';

$database = new Database();
$pdo = $database->getConnection();

// Initialize Hybrid Database Wrapper
$jsonBasePath = __DIR__ . '/../DatabaseJSON';
$db = new HybridPDO($pdo, $jsonBasePath);

// Mark database availability
if (!$db->isOffline()) {
    define('DB_OK', true);
    // Automatically attempt to sync pending changes if back online
    $db->syncPending();
} else {
    define('DB_OK', false);
    // In hybrid mode, we don't need NullDB as HybridPDO handles the fallback
    $msg = "[".date('Y-m-d H:i:s')."] [INFO] [DB] System running in OFFLINE mode (JSON fallback)." . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/process.log', $msg, FILE_APPEND | LOCK_EX);
}

// System constants
// Auto-detect SITE_URL for portability between local and production servers
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Calculate path relative to document root
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $dirPath = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $basePath = str_replace($docRoot, '', $dirPath);
    
    define('SITE_URL', rtrim($protocol . $domainName . $basePath, '/'));
}

define('SITE_NAME', 'Clinic Appointment System');
define('ADMIN_EMAIL', 'ziabul@duck.com');

// Binary Paths (for tools and backups)
// Detect PHP and MySQL paths automatically
$phpBin = 'php'; // Default to path
$mysqlBin = 'mysqldump'; // Default to path

$commonPhpPaths = [
    'C:\xampp\php\php.exe',
    '/usr/bin/php',
    '/usr/local/bin/php'
];
foreach ($commonPhpPaths as $path) {
    if (@file_exists($path)) { $phpBin = $path; break; }
}

$commonMysqlPaths = [
    'C:\xampp\mysql\bin\mysqldump.exe',
    '/usr/bin/mysqldump',
    '/usr/local/bin/mysqldump'
];
foreach ($commonMysqlPaths as $path) {
    if (@file_exists($path)) { $mysqlBin = $path; break; }
}

define('PHP_BIN', $phpBin);
define('MYSQLDUMP_BIN', $mysqlBin);

// For quick local setups where login is failing, enable BYPASS_AUTH=true to auto-login as first admin user.
// WARNING: This bypasses authentication. Do NOT enable in production.
define('BYPASS_AUTH', false);

// Mail settings
define('MAIL_FROM', 'eng.zim.babu@gmail.com');
define('MAIL_FROM_NAME', 'Advance Clinic System By Zim');

define('MAIL_SMTP_HOST', 'smtp.gmail.com');
define('MAIL_SMTP_PORT', 587);
define('MAIL_SMTP_USER', 'eng.zim.babu@gmail.com');
define('MAIL_SMTP_PASS', 'tqdpjbzfzurskaqh');  // your 16-char app password, without spaces
define('MAIL_SMTP_SECURE', 'tls');  // tls (you can also use ssl with port 465)
// If true, prefer SMTP via PHPMailer and do NOT fall back to PHP mail().
// Set to false if you want to allow PHP's mail() fallback (requires local MTA).
define('MAIL_FORCE_SMTP', true);

// Include shared helper functions
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Auto-login bypass (safe for local/dev when explicitly enabled)
if (defined('BYPASS_AUTH') && BYPASS_AUTH && !isset($_SESSION['user_id'])) {
    try {
        $qa = $db->prepare("SELECT user_id, username, role, doctor_id FROM users WHERE role IN ('admin','receptionist','doctor') ORDER BY user_id ASC LIMIT 1");
        $qa->execute();
        if ($qa->rowCount() > 0) {
            $ra = $qa->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $ra['user_id'];
            $_SESSION['username'] = $ra['username'];
            $_SESSION['role'] = strtolower($ra['role']);
            $_SESSION['doctor_id'] = $ra['doctor_id'] ?? null;
            $msg = "[".date('Y-m-d H:i:s')."] [INFO] [AUTO_LOGIN] Auto-logged in as " . $ra['username'] . PHP_EOL;
            file_put_contents(__DIR__ . '/../logs/process.log', $msg, FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        error_log('Auto-login failed: ' . $e->getMessage());
    }
}

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    // If headers not sent, use standard HTTP redirect
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    }

    // Headers already sent (output began). Use JavaScript and meta-refresh fallback.
    $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo "<script>window.location.href='{$safe}';</script>";
    echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url={$safe}\"></noscript>";
    exit();
}

function logAction($action, $details = "") {
    $log_dir = __DIR__ . "/../logs/";
    $log_file = $log_dir . "process.log";
    $timestamp = date("Y-m-d H:i:s");
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_entry = "--------------------------------------------------\n";
    $log_entry .= "TIME: $timestamp\n";
    $log_entry .= "USER: $user (IP: $ip)\n";
    $log_entry .= "ACTION: $action\n";
    $log_entry .= "DETAILS: $details\n";
    $log_entry .= "--------------------------------------------------\n\n";

    if (file_exists($log_file)) {
        $existing = file_get_contents($log_file);
        file_put_contents($log_file, $log_entry . $existing, LOCK_EX);
    } else {
        file_put_contents($log_file, $log_entry, LOCK_EX);
    }

    // Also update recent log
    $recent_file = $log_dir . 'process_recent.log';
    if (file_exists($recent_file)) {
        $existing = file_get_contents($recent_file);
        file_put_contents($recent_file, "[$timestamp] $user | $action | $details\n" . $existing, LOCK_EX);
    } else {
        file_put_contents($recent_file, "[$timestamp] $user | $action | $details\n", LOCK_EX);
    }
}

// Log authentication events and active users
function logAuth($event, $username = '', $user_id = null) {
    $log_dir = __DIR__ . "/../logs/";
    if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);

    $timestamp = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $auth_entry = "==================================================\n";
    $auth_entry .= "AUTH EVENT: $event\n";
    $auth_entry .= "TIME: $timestamp\n";
    $auth_entry .= "USER: $username (ID: " . ($user_id ?? 'N/A') . ")\n";
    $auth_entry .= "IP ADDRESS: $ip\n";
    $auth_entry .= "==================================================\n\n";

    // Write to process.log (prepend)
    $log_file = $log_dir . "process.log";
    if (file_exists($log_file)) {
        $existing = file_get_contents($log_file);
        file_put_contents($log_file, $auth_entry . $existing, LOCK_EX);
    } else {
        file_put_contents($log_file, $auth_entry, LOCK_EX);
    }

    // Write to active_users.log (prepend)
    $active_file = $log_dir . 'active_users.log';
    if (file_exists($active_file)) {
        $existing = file_get_contents($active_file);
        file_put_contents($active_file, $auth_entry . $existing, LOCK_EX);
    } else {
        file_put_contents($active_file, $auth_entry, LOCK_EX);
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($allowed_roles) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Access denied. Insufficient permissions.";
        redirect("../index.php");
    }

    $currentRole = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
    $allowedLower = array_map('strtolower', (array)$allowed_roles);
    if (!in_array($currentRole, $allowedLower)) {
        $_SESSION['error'] = "Access denied. Insufficient permissions.";
        redirect("../index.php");
    }
}
?>
