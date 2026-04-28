<?php
// Tool: send_prescription_test.php
// Usage: php send_prescription_test.php
// Sends the latest prescription (DB or file) for appointment_id=5 to ziabul@duck.com

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$appointment_id = 5;
$to = 'ziabul@duck.com';

try {
    $html = '';
    // Prefer DB-backed prescription
    if ($db instanceof PDO) {
        $q = $db->prepare('SELECT content FROM prescriptions WHERE appointment_id = :aid ORDER BY created_at DESC LIMIT 1');
        $q->bindParam(':aid', $appointment_id);
        $q->execute();
        if ($q->rowCount()) {
            $html = $q->fetchColumn();
        }
    }
    // Fallback to file storage
    if (empty($html)) {
        $dir = __DIR__ . '/../prescriptions';
        $files = [];
        if (is_dir($dir)) {
            $files = glob($dir . "/prescription_{$appointment_id}_*.html");
            if ($files) {
                usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
                $html = file_get_contents($files[0]);
            }
        }
    }
    if (empty($html)) {
        echo "No prescription found for appointment {$appointment_id}\n";
        exit(1);
    }

    // Build email
    $sub = SITE_NAME . ' - Prescription (test)';
    $body = '<p>Test prescription for appointment #' . htmlspecialchars($appointment_id) . '</p>' . $html;

    echo "Sending to {$to}...\n";
    $sent = sendMail($to, $sub, $body);
    if ($sent) {
        echo "Email sent to {$to}\n";
        exit(0);
    } else {
        echo "Failed to send email to {$to} — check logs for PHPMailer output.\n";
        exit(2);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(3);
}
