<?php
// generate_reset_token.php
// Usage (PowerShell):
//   C:\xampp\php\php.exe C:\xampp\htdocs\clinicapp\generate_reset_token.php --username=drbrown
//   C:\xampp\php\php.exe C:\xampp\htdocs\clinicapp\generate_reset_token.php --email=dr.brown@example.com

require_once __DIR__ . '/config/config.php';

// CLI only
if (php_sapi_name() !== 'cli') {
    echo "This script is CLI-only.\n";
    exit(1);
}

$opts = [];
foreach ($argv as $a) {
    if (strpos($a, '--') === 0) {
        $p = explode('=', $a, 2);
        $k = $p[0];
        $v = isset($p[1]) ? $p[1] : null;
        $opts[$k] = $v;
    }
}

$username = $opts['--username'] ?? null;
$email = $opts['--email'] ?? null;

if (!$username && !$email) {
    echo "Usage: php generate_reset_token.php --username=<username> OR --email=<email>\n";
    exit(1);
}

try {
    if ($username) {
        $q = $db->prepare('SELECT user_id, username, email FROM users WHERE username = :u LIMIT 1');
        $q->bindParam(':u', $username);
    } else {
        $q = $db->prepare('SELECT user_id, username, email FROM users WHERE email = :e LIMIT 1');
        $q->bindParam(':e', $email);
    }
    $q->execute();
    if ($q->rowCount() == 0) {
        echo "No user found matching the provided identifier.\n";
        exit(2);
    }
    $user = $q->fetch(PDO::FETCH_ASSOC);

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
    $ins->bindParam(':user_id', $user['user_id']);
    $ins->bindParam(':token', $token);
    $ins->bindParam(':expires_at', $expires);
    $ins->execute();

    $resetUrl = rtrim(SITE_URL, '/') . '/pages/password_reset.php?token=' . $token;

    echo "Password reset token generated for user: " . $user['username'] . " (ID: " . $user['user_id'] . ")\n";
    echo "Expires at: $expires\n";
    echo "Reset URL:\n$resetUrl\n";
    echo "\nOpen that URL in a browser and set a new password.\n";
    exit(0);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(3);
}

?>
