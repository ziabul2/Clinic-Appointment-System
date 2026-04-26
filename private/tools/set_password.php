<?php
// Usage: php tools/set_password.php username new_password
error_reporting(E_ALL);
ini_set('display_errors', 1);
if ($argc < 3) {
    echo "Usage: php tools/set_password.php <username> <new_password>" . PHP_EOL;
    exit(1);
}
$username = $argv[1];
$newpw = $argv[2];
require_once __DIR__ . '/../config/config.php';

try {
    $hash = password_hash($newpw, PASSWORD_DEFAULT);
    $u = $db->prepare('UPDATE users SET password = :pw WHERE username = :u');
    $u->bindParam(':pw', $hash);
    $u->bindParam(':u', $username);
    if ($u->execute()) {
        echo "Password updated for user: $username" . PHP_EOL;
        exit(0);
    } else {
        echo "Failed to update password. Check username." . PHP_EOL;
        exit(2);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(3);
}
