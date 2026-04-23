<?php
// Simple mail test script. Run via: php mail_test.php
require_once __DIR__ . '/config/config.php';
// Ensure functions are loaded
if (!function_exists('sendSMTPMail')) {
    echo "sendSMTPMail function not available.\n";
    exit(1);
}

$to = MAIL_SMTP_USER ?: 'user@example.com';
$subject = 'Appointment Confirmation - Test';
$body = '<p>This is a test of the SMTP mail function from the Clinic app.</p><p>If you see this, SMTP send was successful.</p>';

// Try with debug true so PHPMailer will output debug to error_log (if available)
$result = sendSMTPMail($to, $subject, $body, true, true);

if ($result) {
    echo "Mail send returned: SUCCESS\n";
} else {
    echo "Mail send returned: FAILURE. Check logs (logs/errors.log and PHP error log).\n";
}

?>