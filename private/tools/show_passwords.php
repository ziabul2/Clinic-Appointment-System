<?php
require_once __DIR__ . '/../../config/config.php';
header_remove();
echo "User passwords (for debugging only)\n";
try {
    $q = $db->prepare("SELECT user_id, username, email, password FROM users ORDER BY created_at DESC LIMIT 10");
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo sprintf("#%s %s <%s> -> %s\n", $r['user_id'], $r['username'], $r['email'], $r['password']);
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
