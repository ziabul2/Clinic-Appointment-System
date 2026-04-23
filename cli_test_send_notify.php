<?php
require_once 'config/config.php';
// CLI-only test: pick first user with an email and create a notification + attempt email
try {
    $q = $db->prepare('SELECT user_id, email, username FROM users WHERE email IS NOT NULL AND email != "" LIMIT 1');
    $q->execute();
    $u = $q->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        echo "No user with email found in users table.\n";
        exit(1);
    }
    $uid = $u['user_id'];
    $email = $u['email'];
    $username = $u['username'];
    echo "Found user: $username <$email> (id=$uid)\n";

    // create notification
    $title = 'CLI Test Notification';
    $message = 'This is a test notification created by CLI at ' . date('c');
    $meta = json_encode(['cli_test' => true]);
    $ins = $db->prepare('INSERT INTO notifications (user_id, type, title, message, meta, created_at) VALUES (:uid, :type, :title, :message, :meta, NOW())');
    $type = 'cli_test';
    $ins->bindParam(':uid', $uid);
    $ins->bindParam(':type', $type);
    $ins->bindParam(':title', $title);
    $ins->bindParam(':message', $message);
    $ins->bindParam(':meta', $meta);
    $ins->execute();
    $nid = $db->lastInsertId();
    echo "Inserted notification id=$nid\n";

    // try sending email
    $subject = 'CLI Test Email from ' . SITE_NAME;
    $body = '<p>Hello ' . htmlspecialchars($username) . ',</p><p>This is a test email sent from CLI at ' . date('c') . '.</p>';
    $res = sendSMTPMail($email, $subject, $body, true, false);
    if ($res) echo "Email sent successfully to $email\n";
    else echo "Failed to send email to $email (check logs).\n";

    echo "Done. Check the web UI (notification bell) for the new notification.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
