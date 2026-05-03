<?php
$db = new PDO("sqlite:DatabaseSQL/clinic_offline.db");
$stmt = $db->query("SELECT user_id, username, profile_picture FROM users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['user_id'] . " | User: " . $row['username'] . " | Pic: " . ($row['profile_picture'] ?: 'NULL') . "\n";
}
