<?php
require 'config/config.php';
// Insert a message from 1 to 11
$stmt = $db->prepare("INSERT INTO staff_chat_messages (sender_id, receiver_id, message, is_read, is_deleted) VALUES (1, 11, 'This is a test unread message', 0, 0)");
$stmt->execute();
echo "Inserted unread message.\n";

$user_id = 11;
$stmt = $db->prepare("SELECT sender_id, COUNT(*) as unread FROM staff_chat_messages WHERE receiver_id = :uid AND is_read = 0 AND is_deleted = 0 GROUP BY sender_id");
$stmt->execute(['uid' => $user_id]);
$unread_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

print_r($unread_counts);
echo "\nJSON: " . json_encode(['unread' => $unread_counts]);
