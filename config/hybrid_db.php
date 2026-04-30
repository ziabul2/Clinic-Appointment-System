<?php
/**
 * Hybrid Database Layer
 * Provides seamless fallback to JSON files when MySQL is unavailable.
 */

class JsonDataStore {
    private $basePath;
    private $pendingSyncFile;
    private $dbName = 'clinic_management';

    public function __construct($basePath) {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->pendingSyncFile = $this->basePath . 'pending_sync.json';
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    /**
     * Reads a table from JSON file.
     * Handles the specific phpMyAdmin export format.
     */
    public function readTable($tableName) {
        $file = $this->basePath . "clinic_management_table_{$tableName}.json";
        if (!file_exists($file)) return [];

        $content = file_get_contents($file);
        // Handle leading comma if present (common in phpMyAdmin JSON exports)
        $content = trim($content);
        if (strpos($content, ',') === 0) {
            $content = substr($content, 1);
        }
        
        $json = json_decode($content, true);
        if (!$json || !isset($json['data'])) {
            // Check if it's a plain array (fallback)
            if (is_array($json)) return $json;
            // Check if it's wrapped in [ ] and the first element is the table object
            $wrapped = json_decode('[' . $content . ']', true);
            if ($wrapped && isset($wrapped[0]['data'])) return $wrapped[0]['data'];
            return [];
        }

        return $json['data'];
    }

    /**
     * Writes a table to JSON file.
     */
    public function writeTable($tableName, $data) {
        $file = $this->basePath . "clinic_management_table_{$tableName}.json";
        $payload = [
            'type' => 'table',
            'name' => $tableName,
            'database' => $this->dbName,
            'data' => $data
        ];
        
        // Use LOCK_EX for atomic writes
        file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Records a change to the pending sync log.
     */
    public function recordChange($type, $table, $data, $where = null) {
        $pending = [];
        if (file_exists($this->pendingSyncFile)) {
            $pending = json_decode(file_get_contents($this->pendingSyncFile), true) ?: [];
        }

        $pending[] = [
            'id' => uniqid('sync_', true),
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type, // INSERT, UPDATE, DELETE
            'table' => $table,
            'data' => $data,
            'where' => $where
        ];

        file_put_contents($this->pendingSyncFile, json_encode($pending, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Streams through a large JSON file to find matches.
     * Used for medicine_master_data.
     */
    public function searchStreamed($tableName, $query, $limit = 30) {
        $file = $this->basePath . "clinic_management_table_{$tableName}.json";
        if (!file_exists($file)) return [];

        $handle = fopen($file, 'r');
        if (!$handle) return [];

        $results = [];
        $count = 0;
        $query = strtolower($query);

        while (($line = fgets($handle)) !== false) {
            // Simple keyword search on the raw line for speed
            if (stripos($line, $query) !== false) {
                // Extract the JSON object from the line
                // Lines in these exports usually look like {"id":"1",...}, or {"id":"1",...}
                $line = trim($line);
                $line = rtrim($line, ',');
                if (strpos($line, '{') === 0) {
                    $record = json_decode($line, true);
                    if ($record) {
                        // Check if it's a match across any field (mimicking LIKE %q%)
                        $match = false;
                        foreach ($record as $val) {
                            if (is_string($val) && stripos($val, $query) !== false) {
                                $match = true;
                                break;
                            }
                        }
                        if ($match) {
                            $results[] = $record;
                            $count++;
                            if ($count >= $limit) break;
                        }
                    }
                }
            }
        }

        fclose($handle);
        return $results;
    }

    public function getPendingChanges() {
        if (!file_exists($this->pendingSyncFile)) return [];
        return json_decode(file_get_contents($this->pendingSyncFile), true) ?: [];
    }

    public function clearPendingChanges() {
        if (file_exists($this->pendingSyncFile)) {
            unlink($this->pendingSyncFile);
        }
    }
}

class HybridPDO {
    private $pdo;
    private $store;
    private $isOffline = false;
    private $lastInsertId = null;

    public function __construct($pdo, $jsonBasePath) {
        $this->pdo = $pdo;
        $this->store = new JsonDataStore($jsonBasePath);
        $this->isOffline = !($pdo instanceof PDO);
    }

    public function isOffline() {
        return $this->isOffline;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function prepare($sql) {
        if (!$this->isOffline) {
            try {
                return new HybridStatement($this->pdo->prepare($sql), $this->store, $sql, false, $this);
            } catch (Exception $e) {
                $this->isOffline = true;
            }
        }
        return new HybridStatement(null, $this->store, $sql, true, $this);
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
        return $this->lastInsertId;
    }

    public function setLastInsertId($id) {
        $this->lastInsertId = $id;
    }

    public function beginTransaction() {
        if (!$this->isOffline) return $this->pdo->beginTransaction();
        return true;
    }

    public function commit() {
        if (!$this->isOffline) return $this->pdo->commit();
        return true;
    }

    public function rollBack() {
        if (!$this->isOffline) return $this->pdo->rollBack();
        return true;
    }

    public function syncPending() {
        if ($this->isOffline || !($this->pdo instanceof PDO)) return false;

        $changes = $this->store->getPendingChanges();
        if (empty($changes)) return true;

        $successCount = 0;
        foreach ($changes as $change) {
            try {
                $sql = '';
                $params = $change['data'] ?: [];
                
                if ($change['type'] === 'INSERT') {
                    $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($params)));
                    $placeholders = ':' . implode(', :', array_keys($params));
                    $sql = "INSERT INTO `{$change['table']}` ($cols) VALUES ($placeholders)";
                } elseif ($change['type'] === 'UPDATE') {
                    $sets = [];
                    foreach ($params as $col => $val) $sets[] = "`$col` = :$col";
                    $whereSql = '';
                    if ($change['where']) {
                        foreach ($change['where'] as $col => $val) {
                            $whereSql .= " AND `$col` = :where_$col";
                            $params["where_$col"] = $val;
                        }
                    }
                    $sql = "UPDATE `{$change['table']}` SET " . implode(', ', $sets) . " WHERE 1=1 $whereSql";
                } elseif ($change['type'] === 'DELETE') {
                    $whereSql = '';
                    foreach ($change['where'] as $col => $val) {
                        $whereSql .= " AND `$col` = :where_$col";
                        $params["where_$col"] = $val;
                    }
                    $sql = "DELETE FROM `{$change['table']}` WHERE 1=1 $whereSql";
                }

                if ($sql) {
                    $stmt = $this->pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        $successCount++;
                    }
                }
            } catch (Exception $e) {
                error_log("Sync failed for item {$change['id']}: " . $e->getMessage());
            }
        }

        if ($successCount === count($changes)) {
            $this->store->clearPendingChanges();
            return true;
        }

        return false;
    }
}

class HybridStatement {
    private $stmt;
    private $store;
    private $sql;
    private $isOffline;
    private $results = [];
    private $cursor = 0;
    private $rowCount = 0;
    private $boundParams = [];
    private $parent;

    public function __construct($stmt, $store, $sql, $isOffline, $parent) {
        $this->stmt = $stmt;
        $this->store = $store;
        $this->sql = $sql;
        $this->isOffline = $isOffline;
        $this->parent = $parent;
    }

    public function bindParam($param, &$variable, $type = PDO::PARAM_STR, $length = null, $driver_options = null) {
        if (!$this->isOffline) return $this->stmt->bindParam($param, $variable, $type, $length, $driver_options);
        $this->boundParams[$param] = $variable;
        return true;
    }

    public function bindValue($param, $value, $type = PDO::PARAM_STR) {
        if (!$this->isOffline) return $this->stmt->bindValue($param, $value, $type);
        $this->boundParams[$param] = $value;
        return true;
    }

    public function execute($params = null) {
        if (!$this->isOffline) {
            try {
                $success = $this->stmt->execute($params);
                if ($success && $this->isWriteQuery()) {
                    $this->triggerJsonExport();
                }
                return $success;
            } catch (Exception $e) {
                $this->isOffline = true;
            }
        }

        if ($params) {
            foreach ($params as $k => $v) {
                $key = (strpos($k, ':') === 0) ? $k : ':' . $k;
                $this->boundParams[$key] = $v;
            }
        }

        return $this->executeOffline();
    }

    private function isWriteQuery() {
        $sql = strtoupper(trim($this->sql));
        return (strpos($sql, 'INSERT') === 0 || strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0);
    }

    private function triggerJsonExport() {
        // Extract table name from query
        if (preg_match('/(?:INTO|UPDATE|FROM)\s+[`]?([a-zA-Z0-9_]+)[`]?/i', $this->sql, $m)) {
            $table = $m[1];
            // Skip large tables for auto-export to avoid performance hits
            $skip = ['medicine_master_data', 'ai_logs'];
            if (in_array($table, $skip)) return;

            try {
                $data = $this->parent->getPdo()->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                $this->store->writeTable($table, $data);
            } catch (Exception $e) {
                error_log("Failed to auto-export table $table: " . $e->getMessage());
            }
        }
    }

    private function executeOffline() {
        $sql = trim($this->sql);
        
        if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+[`]?([a-zA-Z0-9_]+)[`]?(\s+WHERE\s+(.+))?(\s+GROUP\s+BY\s+.+)?(\s+ORDER\s+BY\s+.+)?(\s+LIMIT\s+.+)?$/i', $sql, $matches)) {
            $table = $matches[2];
            $where = isset($matches[4]) ? $matches[4] : null;

            if ($table === 'medicine_master_data' && $where && strpos($where, 'LIKE') !== false) {
                $searchTerm = '';
                foreach ($this->boundParams as $val) {
                    if (is_string($val)) { $searchTerm = trim($val, '%'); break; }
                }
                $this->results = $this->store->searchStreamed($table, $searchTerm);
            } else {
                $data = $this->store->readTable($table);
                if ($where) {
                    $this->results = $this->filterData($data, $where);
                } else {
                    $this->results = $data;
                }
            }
            $this->rowCount = count($this->results);
            return true;
        }

        if (preg_match('/^INSERT\s+INTO\s+[`]?([a-zA-Z0-9_]+)[`]?\s*\((.+?)\)\s*VALUES\s*\((.+?)\)/i', $sql, $matches)) {
            $table = $matches[1];
            $cols = array_map('trim', explode(',', $matches[2]));
            $placeholders = array_map('trim', explode(',', $matches[3]));
            
            $newRow = [];
            foreach ($cols as $i => $col) {
                $col = trim($col, '` ');
                $ph = trim($placeholders[$i]);
                $newRow[$col] = $this->boundParams[$ph] ?? null;
            }

            $data = $this->store->readTable($table);
            $maxId = 0;
            $idCol = "{$table}_id";
            if ($table === 'users') $idCol = 'user_id';
            
            foreach ($data as $row) {
                if (isset($row[$idCol]) && $row[$idCol] > $maxId) $maxId = intval($row[$idCol]);
            }
            $newId = $maxId + 1;
            if (!isset($newRow[$idCol]) || empty($newRow[$idCol])) {
                $newRow[$idCol] = $newId;
                $this->parent->setLastInsertId($newId);
            }

            $data[] = $newRow;
            $this->store->writeTable($table, $data);
            $this->store->recordChange('INSERT', $table, $newRow);
            $this->rowCount = 1;
            return true;
        }

        if (preg_match('/^UPDATE\s+[`]?([a-zA-Z0-9_]+)[`]?\s+SET\s+(.+?)(\s+WHERE\s+(.+))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $setStr = $matches[2];
            $where = isset($matches[4]) ? $matches[4] : null;

            $data = $this->store->readTable($table);
            $updateCount = 0;
            
            $updates = [];
            preg_match_all('/([a-zA-Z0-9_`]+)\s*=\s*(:[a-zA-Z0-9_]+)/', $setStr, $updateMatches);
            foreach ($updateMatches[1] as $i => $col) {
                $updates[trim($col, '` ')] = $this->boundParams[$updateMatches[2][$i]];
            }

            foreach ($data as &$row) {
                if (!$where || $this->matchRow($row, $where)) {
                    foreach ($updates as $col => $val) $row[$col] = $val;
                    $updateCount++;
                }
            }

            if ($updateCount > 0) {
                $this->store->writeTable($table, $data);
                $this->store->recordChange('UPDATE', $table, $updates, $this->parseWhereToParams($where));
            }
            $this->rowCount = $updateCount;
            return true;
        }

        if (preg_match('/^DELETE\s+FROM\s+[`]?([a-zA-Z0-9_]+)[`]?(\s+WHERE\s+(.+))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $where = isset($matches[3]) ? $matches[3] : null;
            $data = $this->store->readTable($table);
            $newData = [];
            $deleteCount = 0;
            foreach ($data as $row) {
                if (!$where || $this->matchRow($row, $where)) { $deleteCount++; continue; }
                $newData[] = $row;
            }
            if ($deleteCount > 0) {
                $this->store->writeTable($table, $newData);
                $this->store->recordChange('DELETE', $table, null, $this->parseWhereToParams($where));
            }
            $this->rowCount = $deleteCount;
            return true;
        }

        return false;
    }

    private function filterData($data, $where) {
        $filtered = [];
        foreach ($data as $row) {
            if ($this->matchRow($row, $where)) $filtered[] = $row;
        }
        return $filtered;
    }

    private function matchRow($row, $where) {
        $parts = preg_split('/\s+AND\s+/i', $where);
        foreach ($parts as $part) {
            if (preg_match('/([a-zA-Z0-9_`]+)\s*=\s*(:[a-zA-Z0-9_]+)/', $part, $m)) {
                $col = trim($m[1], '` ');
                $ph = $m[2];
                $expected = $this->boundParams[$ph] ?? null;
                if (!isset($row[$col]) || $row[$col] != $expected) return false;
            }
        }
        return true;
    }

    private function parseWhereToParams($where) {
        if (!$where) return null;
        $params = [];
        preg_match_all('/([a-zA-Z0-9_`]+)\s*=\s*(:[a-zA-Z0-9_]+)/', $where, $m);
        foreach ($m[1] as $i => $col) {
            $params[trim($col, '` ')] = $this->boundParams[$m[2][$i]];
        }
        return $params;
    }

    public function fetch($mode = PDO::FETCH_ASSOC) {
        if (!$this->isOffline) return $this->stmt->fetch($mode);
        if ($this->cursor < count($this->results)) return $this->results[$this->cursor++];
        return false;
    }

    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        if (!$this->isOffline) return $this->stmt->fetchAll($mode);
        return $this->results;
    }

    public function rowCount() {
        if (!$this->isOffline) return $this->stmt->rowCount();
        return $this->rowCount;
    }

    public function fetchColumn($column_number = 0) {
        if (!$this->isOffline) return $this->stmt->fetchColumn($column_number);
        $row = $this->fetch();
        if ($row) {
            $vals = array_values($row);
            return $vals[$column_number] ?? null;
        }
        return false;
    }
}
