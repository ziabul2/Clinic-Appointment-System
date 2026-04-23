<?php
require_once __DIR__ . '/../config/config.php';
if (!isLoggedIn()) redirect('../index.php');
header('Content-Type: text/plain');
echo "User session dump:\n";
echo "username: " . ($_SESSION['username'] ?? '[not set]') . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? '[not set]') . "\n";
echo "role: " . ($_SESSION['role'] ?? '[not set]') . "\n";
echo "doctor_id: " . ($_SESSION['doctor_id'] ?? '[not set]') . "\n";
echo "\n--- session raw ---\n";
print_r($_SESSION);
exit;
