<?php
// Dump users for debugging (CLI only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/config.php';

echo "Users dump - " . date('Y-m-d H:i:s') . PHP_EOL;
$q = $db->query('SELECT user_id, username, email, role, password FROM users');
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: {$r['user_id']} | username: {$r['username']} | email: {$r['email']} | role: {$r['role']}" . PHP_EOL;
    echo "  password: {$r['password']}" . PHP_EOL;
}
echo "Total: " . count($rows) . PHP_EOL;
