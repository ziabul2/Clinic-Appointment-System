<?php
require 'config/config.php';
$_SESSION['user_id'] = 11; // Assuming 11 is reception
$user_id = 11;

$stmt = $db->prepare("SELECT sender_id, COUNT(*) as unread FROM staff_chat_messages WHERE receiver_id = :uid AND is_read = 0 AND is_deleted = 0 GROUP BY sender_id");
$stmt->execute(['uid' => $user_id]);
$unread_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

print_r($unread_counts);

$stmt = $db->query("SELECT * FROM staff_chat_messages ORDER BY id DESC LIMIT 2");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
