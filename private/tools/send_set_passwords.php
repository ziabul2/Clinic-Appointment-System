<?php
/**
 * send_set_passwords.php
 * Scans `users` for rows with non-bcrypt passwords and sends a set-password token email.
 * Usage: php send_set_passwords.php
 */
require_once __DIR__ . '/../../config/config.php';

function out($s) { echo $s . PHP_EOL; }

try {
    out('Scanning users for non-hashed passwords...');
    $q = $db->prepare("SELECT user_id, username, email, password FROM users ORDER BY user_id ASC");
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    $count = 0; $sent = 0; $skipped = 0;
    foreach ($rows as $r) {
        $count++;
        $pw = $r['password'] ?? '';
        // If empty email, skip
        if (empty($r['email'])) {
            $skipped++; out("#{$r['user_id']} {$r['username']}: no email, skipped");
            continue;
        }
        // detect bcrypt hash
        if (preg_match('/^\$2[aby]\$[0-9]{2}\$/', $pw)) {
            out("#{$r['user_id']} {$r['username']}: already hashed");
            continue;
        }
        // generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
        $ins->bindValue(':user_id', $r['user_id']);
        $ins->bindValue(':token', $token);
        $ins->bindValue(':expires_at', $expires);
        $ins->execute();
        // send email using sendPasswordResetLink helper if available, otherwise fallback
        try {
            if (function_exists('sendPasswordResetLink')) {
                sendPasswordResetLink($r['email'], $r['username'], $token);
                out("#{$r['user_id']} {$r['username']}: token sent to {$r['email']}");
            } else {
                // minimal email body
                $link = rtrim(SITE_URL, '/') . '/pages/password_reset.php?token=' . urlencode($token);
                $subject = SITE_NAME . ' - Set your password';
                $body = "<p>Hello " . htmlspecialchars($r['username']) . ",</p>\n";
                $body .= "<p>Please set your account password using this link (valid 24 hours): <a href=\"$link\">Set password</a></p>\n";
                $body .= "<p>Regards,<br>" . SITE_NAME . "</p>";
                sendMail($r['email'], $subject, $body);
                out("#{$r['user_id']} {$r['username']}: mail sent via sendMail to {$r['email']}");
            }
            $sent++;
        } catch (Exception $e) {
            out("#{$r['user_id']} {$r['username']}: error sending email: " . $e->getMessage());
        }
    }
    out("Done. Users scanned: $count, emails sent: $sent, skipped (no email): $skipped");
} catch (Exception $e) {
    out('Error: ' . $e->getMessage());
}

?>
