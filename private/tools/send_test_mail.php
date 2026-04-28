<?php
// Send a single test email using application's mail helper.
require_once __DIR__ . '/../../config/config.php';
if (!function_exists('sendSMTPMail') && file_exists(__DIR__ . '/../../includes/functions.php')) {
    require_once __DIR__ . '/../../includes/functions.php';
}


$to = ADMIN_EMAIL;
$subject = SITE_NAME . ' - Test Email from Clinic App';
$body = '<p>This is a test email sent from the Clinic application to verify SMTP settings.</p>';

echo "Sending test email to: $to\n";
$sent = false;
if (function_exists('sendSMTPMail')) {
    $sent = sendSMTPMail($to, $subject, $body, true, false);
} elseif (function_exists('sendMail')) {
    $sent = sendMail($to, $subject, $body);
}

if ($sent) {
    echo "Test email sent successfully to $to\n";
} else {
    echo "Failed to send test email to $to. Check logs/errors.log for details.\n";
}
