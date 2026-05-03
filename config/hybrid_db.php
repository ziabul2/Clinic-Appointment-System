<?php
/**
 * Hybrid Database Layer
 * Provides seamless fallback to SQLite when MySQL is unavailable.
 */

class SQLiteDataStore {
    private $dbPath;
    private $sqlite;

    public function __construct($dbPath) {
        $this->dbPath = $dbPath;
        $this->init();
    }

    private function init() {
        try {
            $dir = dirname($this->dbPath);
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $this->sqlite = new PDO("sqlite:" . $this->dbPath);
            $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create pending sync table if not exists
            $this->sqlite->exec("CREATE TABLE IF NOT EXISTS `pending_sync` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `sync_id` TEXT NOT NULL,
                `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `query` TEXT NOT NULL,
                `params` TEXT,
                `table_name` TEXT
            )");
        } catch (Exception $e) {
            error_log("SQLite Init Error: " . $e->getMessage());
        }
    }

    public function getSqlite() {
        if (!$this->sqlite) $this->init();
        return $this->sqlite;
    }

    public function recordChange($sql, $params = null) {
        try {
            $tableName = 'unknown';
            if (preg_match('/(?:INTO|UPDATE|FROM|JOIN)\s+[`]?([a-zA-Z0-9_]+)[`]?/i', $sql, $m)) {
                $tableName = $m[1];
            }

            $stmt = $this->getSqlite()->prepare("INSERT INTO pending_sync (sync_id, query, params, table_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                uniqid('sync_', true),
                $sql,
                $params ? json_encode($params) : null,
                $tableName
            ]);
        } catch (Exception $e) {
            error_log("Failed to record offline change: " . $e->getMessage());
        }
    }

    public function getPendingChanges() {
        try {
            return $this->getSqlite()->query("SELECT * FROM pending_sync ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function removePendingChange($id) {
        try {
            $stmt = $this->getSqlite()->prepare("DELETE FROM pending_sync WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Failed to remove pending change: " . $e->getMessage());
        }
    }

    public function clearPendingChanges() {
        try {
            $this->getSqlite()->exec("DELETE FROM pending_sync");
        } catch (Exception $e) {
            error_log("Failed to clear pending changes: " . $e->getMessage());
        }
    }

    public function writeTable($tableName, $data) {
        if (empty($data)) return;
        try {
            $db = $this->getSqlite();
            
            // We assume the table already exists from init_sqlite.php
            // Clear existing data for a full refresh
            $db->exec("DELETE FROM `$tableName` ");
            
            $db->beginTransaction();
            $cols = array_keys($data[0]);
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO `$tableName` (`" . implode("`,`", $cols) . "`) VALUES ($placeholders)";
            $stmt = $db->prepare($sql);
            
            foreach ($data as $row) {
                // Ensure all values are scalar
                foreach ($row as &$v) if (is_array($v) || is_object($v)) $v = json_encode($v);
                $stmt->execute(array_values($row));
            }
            $db->commit();
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log("Failed to write table $table to SQLite: " . $e->getMessage());
        }
    }
}

class HybridPDO {
    private $pdo;
    private $store;
    private $isOffline = false;

    public function __construct($pdo, $sqlitePath) {
        $this->pdo = $pdo;
        $this->store = new SQLiteDataStore($sqlitePath);
        $this->isOffline = !($pdo instanceof PDO);
    }

    public function isOffline() {
        return $this->isOffline;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function getStore() {
        return $this->store;
    }

    public function prepare($sql) {
        if (!$this->isOffline) {
            try {
                return new HybridStatement($this->pdo->prepare($sql), $this->store, $sql, false, $this);
            } catch (Exception $e) {
                $this->isOffline = true;
            }
        }
        
        // Offline logic: translate MySQL-specific syntax to SQLite
        $translatedSql = $sql;
        if ($this->isOffline) {
            // Replace NOW() with datetime('now', 'localtime')
            $translatedSql = preg_replace('/NOW\(\)/i', "datetime('now', 'localtime')", $translatedSql);
            
            // Replace DATE_SUB(NOW(), INTERVAL X DAY) with datetime('now', 'localtime', '-X day')
            $translatedSql = preg_replace('/DATE_SUB\s*\(\s*datetime\(\'now\', \'localtime\'\)\s*,\s*INTERVAL\s+(\d+)\s+DAY\s*\)/i', "datetime('now', 'localtime', '-$1 day')", $translatedSql);
            $translatedSql = preg_replace('/DATE_SUB\s*\(\s*NOW\(\)\s*,\s*INTERVAL\s+(\d+)\s+DAY\s*\)/i', "datetime('now', 'localtime', '-$1 day')", $translatedSql);
            
            // Replace generic INTERVAL X DAY/HOUR/etc
            $translatedSql = preg_replace('/INTERVAL\s+(\d+)\s+DAY/i', "'$1 day'", $translatedSql);
            $translatedSql = preg_replace('/INTERVAL\s+(\d+)\s+HOUR/i', "'$1 hour'", $translatedSql);
            
            // Handle lastInsertId behavior if necessary (not usually in string)
        }

        $sqlite = $this->store->getSqlite();
        if (!$sqlite) return new HybridStatement(null, $this->store, $sql, true, $this);
        return new HybridStatement($sqlite->prepare($translatedSql), $this->store, $sql, true, $this);
    }

    public function query($sql) {
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function exec($sql) {
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function lastInsertId($name = null) {
        if (!$this->isOffline) return $this->pdo->lastInsertId($name);
        return $this->store->getSqlite()->lastInsertId($name);
    }

    public function beginTransaction() {
        if (!$this->isOffline) return $this->pdo->beginTransaction();
        return $this->store->getSqlite()->beginTransaction();
    }

    public function commit() {
        if (!$this->isOffline) return $this->pdo->commit();
        return $this->store->getSqlite()->commit();
    }

    public function rollBack() {
        if (!$this->isOffline) return $this->pdo->rollBack();
        return $this->store->getSqlite()->rollBack();
    }

    public function syncPending() {
        if (!($this->pdo instanceof PDO)) return false;

        $changes = $this->store->getPendingChanges();
        if (empty($changes)) return true;

        $successCount = 0;
        foreach ($changes as $change) {
            try {
                $params = $change['params'] ? json_decode($change['params'], true) : [];
                $stmt = $this->pdo->prepare($change['query']);
                if ($stmt->execute($params)) {
                    $this->store->removePendingChange($change['id']);
                    $successCount++;
                }
            } catch (Exception $e) {
                error_log("Sync failed for item {$change['id']}: " . $e->getMessage());
            }
        }

        return $successCount === count($changes);
    }
}

class HybridStatement {
    private $stmt;
    private $store;
    private $sql;
    private $isOffline;
    private $parent;

    public function __construct($stmt, $store, $sql, $isOffline, $parent) {
        $this->stmt = $stmt;
        $this->store = $store;
        $this->sql = $sql;
        $this->isOffline = $isOffline;
        $this->parent = $parent;
    }

    public function bindParam($param, &$variable, $type = PDO::PARAM_STR, $length = null, $driver_options = null) {
        if (!$this->stmt) return false;
        return $this->stmt->bindParam($param, $variable, $type, $length, $driver_options);
    }

    public function bindValue($param, $value, $type = PDO::PARAM_STR) {
        if (!$this->stmt) return false;
        return $this->stmt->bindValue($param, $value, $type);
    }

    public function execute($params = null) {
        if (!$this->isOffline) {
            try {
                $success = $this->stmt->execute($params);
                if ($success && $this->isWriteQuery()) {
                    $this->triggerExport();
                }
                return $success;
            } catch (Exception $e) {
                // MySQL went away during execution, fallback to SQLite
                $this->isOffline = true;
                $sqlite = $this->store->getSqlite();
                if ($sqlite) $this->stmt = $sqlite->prepare($this->sql);
            }
        }

        // Offline logic
        if (!$this->stmt) return false;
        if ($this->isWriteQuery()) {
            $this->store->recordChange($this->sql, $params);
        }
        
        return $this->stmt->execute($params);
    }

    public function fetch($mode = PDO::FETCH_ASSOC) {
        return $this->stmt ? $this->stmt->fetch($mode) : false;
    }

    public function fetchAll($mode = PDO::FETCH_ASSOC, ...$args) {
        return $this->stmt ? $this->stmt->fetchAll($mode, ...$args) : [];
    }

    public function fetchColumn($column_number = 0) {
        return $this->stmt ? $this->stmt->fetchColumn($column_number) : false;
    }

    public function rowCount() {
        return $this->stmt ? $this->stmt->rowCount() : 0;
    }

    private function isWriteQuery() {
        $sql = strtoupper(trim($this->sql));
        return (strpos($sql, 'INSERT') === 0 || strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0);
    }

    private function triggerExport() {
        // Extract table names
        preg_match_all('/(?:INTO|UPDATE|FROM|JOIN)\s+[`]?([a-zA-Z0-9_]+)[`]?/i', $this->sql, $ms);
        $tables = array_unique($ms[1] ?? []);

        $skip = ['medicine_master_data', 'ai_logs', 'pending_sync'];

        foreach ($tables as $table) {
            if (in_array($table, $skip)) continue;
            try {
                $pdo = $this->parent->getPdo();
                if (!($pdo instanceof PDO)) continue;
                $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                $this->store->writeTable($table, $data);
            } catch (Exception $e) {
                error_log("Failed to export table $table to SQLite: " . $e->getMessage());
            }
        }
    }
}
