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
            // Try one last time to reconnect by including database.php again or checking isOnline
            require_once '../config/database.php';
            if (isOnline()) {
                $newPdo = (Database::getInstance())->getConnection();
                if ($newPdo instanceof PDO) {
                    $db = new HybridPDO($newPdo, __DIR__ . '/../DatabaseJSON');
                }
            }
        }

        if ($db->isOffline()) {
            echo json_encode(['ok' => false, 'message' => 'Cannot sync while offline. MySQL is still unreachable.']);
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

    if ($action === 'rebuild_index') {
        require_once '../config/medicine_cache.php';
        $jsonBasePath = __DIR__ . '/../DatabaseJSON';
        $cache = new MedicineCache($jsonBasePath);
        $res = $cache->rebuildIndex();
        if ($res) {
            echo json_encode(['ok' => true, 'message' => 'Medicine search index rebuilt for super fast performance.']);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Failed to rebuild index. Master file may be missing.']);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
?>
