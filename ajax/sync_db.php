<?php
/**
 * AJAX Database Sync Handler
 */
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'status';

try {
    // HybridPDO $db is already initialized in config.php
    
    if ($action === 'status') {
        $store = new JsonDataStore(__DIR__ . '/../DatabaseJSON');
        $pending = $store->getPendingChanges();
        echo json_encode([
            'ok' => true,
            'is_offline' => $db->isOffline(),
            'pending_count' => count($pending),
            'last_sync' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    if ($action === 'sync') {
        if ($db->isOffline()) {
            echo json_encode(['ok' => false, 'message' => 'Cannot sync while offline.']);
            exit;
        }

        $res = $db->syncPending();
        if ($res) {
            echo json_encode(['ok' => true, 'message' => 'Sync completed successfully.']);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Sync failed or partially completed. Check logs.']);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
?>
