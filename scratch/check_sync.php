<?php
require_once __DIR__ . '/../config/config.php';
echo "Offline: " . ($db->isOffline() ? "YES" : "NO") . "\n";
if (!$db->isOffline()) {
    $pending = $db->getStore()->getPendingChanges();
    echo "Pending changes: " . count($pending) . "\n";
    if (count($pending) > 0) {
        echo "Attempting sync...\n";
        $res = $db->syncPending();
        echo "Sync result: " . ($res ? "SUCCESS" : "FAILED") . "\n";
    }
}
