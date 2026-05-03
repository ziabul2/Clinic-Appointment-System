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
        $pending = $db->getStore()->getPendingChanges();
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
            // Try to reconnect
            require_once '../config/database.php';
            $database = new Database();
            $newPdo = $database->getConnection();
            if ($newPdo instanceof PDO) {
                // Re-initialize with new connection
                $sqlitePath = __DIR__ . '/../DatabaseSQL/clinic_offline.db';
                $db = new HybridPDO($newPdo, $sqlitePath);
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
        if ($db->isOffline()) {
            echo json_encode(['ok' => false, 'message' => 'Cannot rebuild while offline.']);
            exit;
        }

        // Refresh data from MySQL first
        require_once '../config/init_sqlite.php';
        $mysqlFile = __DIR__ . '/../sqls_DB/clinic_management.sql';
        $sqliteFile = __DIR__ . '/../DatabaseSQL/clinic_offline.db';
        $init = new SQLiteInitializer($mysqlFile, $sqliteFile);
        $init->importData($db);
        
        // Then rebuild JSON partitions for the client-side SPA
        require_once '../config/medicine_cache.php';
        $cache = new MedicineCache(__DIR__ . '/../DatabaseSQL', $db);
        $res = $cache->rebuildIndex();
        
        if ($res) {
            echo json_encode(['ok' => true, 'message' => 'Offline database refreshed and medicine index partitions rebuilt.']);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Database refreshed but index rebuild failed.']);
        }
        exit;
    }

    if ($action === 'force_export') {
        if ($db->isOffline()) {
            echo json_encode(['ok' => false, 'message' => 'Cannot export while offline.']);
            exit;
        }

        require_once '../config/init_sqlite.php';
        $mysqlFile = __DIR__ . '/../sqls_DB/clinic_management.sql';
        $sqliteFile = __DIR__ . '/../DatabaseSQL/clinic_offline.db';
        $init = new SQLiteInitializer($mysqlFile, $sqliteFile);
        
        // Full run: recreates schema and imports data
        $init->run();
        $init->importData($db);

        echo json_encode(['ok' => true, 'message' => 'Full database export to SQLite completed.']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
?>
