<?php
require 'config/config.php';
try {
    $user_id = 1;
    $stmt = $db->prepare("
        SELECT u.user_id, u.username, u.role, u.last_activity, u.first_name, u.last_name,
               (SELECT status FROM user_logins WHERE user_id = u.user_id ORDER BY login_time DESC LIMIT 1) as current_status,
               cp.status as permission_status,
               cp.requester_id
        FROM users u
        LEFT JOIN chat_permissions cp ON (cp.requester_id = :uid1 AND cp.target_id = u.user_id) OR (cp.requester_id = u.user_id AND cp.target_id = :uid2)
        WHERE u.user_id != :uid3
        ORDER BY u.last_activity DESC
    ");
    $stmt->execute(['uid1' => $user_id, 'uid2' => $user_id, 'uid3' => $user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
