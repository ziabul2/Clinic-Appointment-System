<?php
// Simple test to run the SELECT used in process.php login to ensure no SQL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/config.php';

try {
    $username = $argv[1] ?? 'admin';
    $q = $db->prepare('SELECT user_id, username, password, role, doctor_id FROM users WHERE username = :u OR email = :e LIMIT 1');
    $q->bindParam(':u', $username);
    $q->bindParam(':e', $username);
    $q->execute();
    if ($q->rowCount() === 0) {
        echo "No user found for $username\n";
        exit(1);
    }
    $r = $q->fetch(PDO::FETCH_ASSOC);
    echo "Found user: id={$r['user_id']}, username={$r['username']}, role={$r['role']}, doctor_id={$r['doctor_id']}\n";
} catch (Exception $e) {
    echo "Error executing login query: " . $e->getMessage() . "\n";
    exit(2);
}
