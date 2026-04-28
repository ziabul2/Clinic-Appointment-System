<?php
require_once __DIR__ . '/../../config/config.php';
$argv = $_SERVER['argv'];
if (count($argv) < 3) {
    echo "Usage: php test_login.php <username_or_email> <password>\n";
    exit(1);
}
$username = $argv[1];
$password = $argv[2];
try {
    $q = $db->prepare('SELECT user_id, username, password, role, doctor_id FROM users WHERE username = :u OR email = :e LIMIT 1');
    $q->bindParam(':u', $username);
    $q->bindParam(':e', $username);
    $q->execute();
    if ($q->rowCount() == 0) {
        echo "User not found\n"; exit(2);
    }
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $stored = $row['password'];
    echo "Found user: " . $row['username'] . " (id=" . $row['user_id'] . ")\n";
    if (preg_match('/^\\$2[aby]\\$[0-9]{2}\\$/', $stored)) {
        echo "Stored password looks hashed (bcrypt).\n";
        if (password_verify($password, $stored)) {
            echo "Password verified OK (hash).\n";
        } else {
            echo "Password mismatch (hash).\n";
        }
    } else {
        echo "Stored password looks plaintext.\n";
        if ($password === $stored) {
            echo "Plaintext password matches. Upgrading to hash...\n";
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $u = $db->prepare('UPDATE users SET password = :pw WHERE user_id = :id');
            $u->bindValue(':pw', $newHash);
            $u->bindValue(':id', $row['user_id']);
            $u->execute();
            echo "Password upgraded to hash.\n";
        } else {
            echo "Plaintext mismatch.\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        echo "PDO Error Info: \n";
        print_r($db->errorInfo());
    }
    echo $e->getTraceAsString() . "\n";
}
?>
