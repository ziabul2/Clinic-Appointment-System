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

    private static $requestCache = [];

    /**
     * Reads a table from JSON file.
     * Handles the specific phpMyAdmin export format.
     */
    public function readTable($tableName) {
        if (isset(self::$requestCache[$tableName])) return self::$requestCache[$tableName];

        $file = $this->basePath . "clinic_management_table_{$tableName}.json";
        if (!file_exists($file)) return [];

        // For extremely large tables, don't read them into memory unless necessary
        // medicine_master_data is usually handled via searchStreamed
        if (filesize($file) > 10 * 1024 * 1024 && $tableName === 'medicine_master_data') {
            return []; // Force use of streaming methods
        }

        $content = file_get_contents($file);
        // Handle leading comma if present (common in phpMyAdmin JSON exports)
        $content = trim($content);
        if (strpos($content, ',') === 0) {
            $content = substr($content, 1);
        }
        
        $json = json_decode($content, true);
        $data = [];
        if (!$json || !isset($json['data'])) {
            if (is_array($json)) $data = $json;
            else {
                $wrapped = json_decode('[' . $content . ']', true);
                if ($wrapped && isset($wrapped[0]['data'])) $data = $wrapped[0]['data'];
            }
        } else {
            $data = $json['data'];
        }

        self::$requestCache[$tableName] = $data;
        return $data;
    }

    /**
     * Counts records in a large JSON file without loading it all.
     */
    public function countStreamed($tableName, $distinctCol = null) {
        $file = $this->basePath . "clinic_management_table_{$tableName}.json";
        if (!file_exists($file)) return 0;

        $handle = fopen($file, 'r');
        if (!$handle) return 0;

        $count = 0;
        $uniques = [];
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (strpos($line, '{') === 0) {
                if ($distinctCol) {
                    $line = rtrim($line, ',');
                    $record = json_decode($line, true);
                    if ($record && isset($record[$distinctCol])) {
                        $uniques[$record[$distinctCol]] = true;
                    }
                } else {
                    $count++;
                }
            }
        }
        fclose($handle);
        return $distinctCol ? count($uniques) : $count;
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
        // Check for cached index first for "Super Fast" medicine search
        if ($tableName === 'medicine_master_data') {
            require_once __DIR__ . '/medicine_cache.php';
            $cache = new MedicineCache($this->basePath);
            if ($cache->isIndexReady()) {
                return $cache->search($query, $limit);
            }
        }

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

    public function getPendingSyncFile() {
        return $this->pendingSyncFile;
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
        if (!($this->pdo instanceof PDO)) return false;

        $changes = $this->store->getPendingChanges();
        if (empty($changes)) return true;

        $successCount = 0;
        $failedIds = [];
        
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
                    foreach ($params as $col => $val) {
                        $sets[] = "`$col` = :set_$col";
                        $params["set_$col"] = $val;
                        unset($params[$col]);
                    }
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
                    } else {
                        $failedIds[] = $change['id'];
                        $err = $stmt->errorInfo();
                        error_log("Sync query failed for item {$change['id']}: " . ($err[2] ?? 'Unknown error'));
                    }
                }
            } catch (Exception $e) {
                $failedIds[] = $change['id'];
                error_log("Sync exception for item {$change['id']}: " . $e->getMessage());
            }
        }

        if ($successCount === count($changes)) {
            $this->store->clearPendingChanges();
            return true;
        }

        // If some succeeded, remove them from the list
        if ($successCount > 0) {
            $remaining = array_filter($changes, fn($c) => in_array($c['id'], $failedIds));
            file_put_contents($this->store->getPendingSyncFile(), json_encode(array_values($remaining), JSON_PRETTY_PRINT), LOCK_EX);
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
        
        // Strip out aliases from table names like "doctors d" or "doctors AS d"
        $sql = preg_replace('/\s+AS\s+([a-zA-Z0-9_]+)/i', ' $1', $sql);
        
        // Handle SELECT queries
        if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+(.+?)(\s+WHERE\s+(.+))?(\s+GROUP\s+BY\s+.+)?(\s+ORDER\s+BY\s+(.+))?(\s+LIMIT\s+(.+))?$/i', $sql, $matches)) {
            $selectCols = trim($matches[1]);
            $fromParts = trim($matches[2]);
            $where = isset($matches[4]) ? $matches[4] : null;
            $orderBy = isset($matches[7]) ? $matches[7] : null;
            $limit = isset($matches[9]) ? $matches[9] : null;

            // Resolve main table and alias reliably
            $mainTable = ''; $mainAlias = '';
            if (preg_match('/[`]?([a-zA-Z0-9_]+)[`]?\s*([a-zA-Z0-9_]+)?/i', explode('JOIN', $fromParts, 2)[0], $fm)) {
                $mainTable = $fm[1];
                $mainAlias = $fm[2] ?: $mainTable;
            }

            // Handle Multiple JOINs (improved)
            if (stripos($fromParts, ' JOIN ') !== false) {
                // Split by JOIN keyword to get tables and conditions
                $joinParts = preg_split('/\s+(LEFT\s+)?JOIN\s+/i', $fromParts);
                $firstTablePart = array_shift($joinParts);
                
                $this->results = $this->store->readTable($mainTable);
                
                // Track aliases to help with column resolving
                $aliases = [$mainAlias => $mainTable];

                foreach ($joinParts as $jp) {
                    // Extract table and ON condition
                    if (preg_match('/[`]?([a-zA-Z0-9_]+)[`]?\s*([a-zA-Z0-9_]+)?\s+ON\s+(.+)/is', $jp, $jm)) {
                        $joinTable = $jm[1];
                        $joinAlias = $jm[2] ?: $joinTable;
                        $onCondition = $jm[3];
                        $joinData = $this->store->readTable($joinTable);
                        $aliases[$joinAlias] = $joinTable;

                        $newResults = [];
                        foreach ($this->results as $row1) {
                            $found = false;
                            foreach ($joinData as $row2) {
                                if ($this->matchComplexJoin($row1, $row2, $onCondition, $mainAlias, $joinAlias)) {
                                    $newResults[] = $this->mergeRows($row1, $row2, $mainAlias, $joinAlias);
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                // Provide a null row matching the join table structure
                                $nullRow = !empty($joinData) ? array_map(fn() => null, $joinData[0]) : [];
                                $newResults[] = $this->mergeRows($row1, $nullRow, $mainAlias, $joinAlias);
                            }
                        }
                        $this->results = $newResults;
                    }
                }
            } else {
                // Simple SELECT
                if ($mainTable === 'medicine_master_data' && $where && strpos($where, 'LIKE') !== false) {
                    $searchTerm = '';
                    foreach ($this->boundParams as $val) if (is_string($val)) { $searchTerm = trim($val, '%'); break; }
                    $this->results = $this->store->searchStreamed($mainTable, $searchTerm);
                } else {
                    $this->results = $this->store->readTable($mainTable);
                }
            }

            // Apply WHERE
            if ($where) {
                $this->results = $this->filterData($this->results, $where);
            }

            // Handle Subqueries in SELECT (Improved for general queries)
            if (preg_match_all('/\(\s*SELECT\s+(.+?)\s+FROM\s+([a-zA-Z0-9_]+)(?:\s+([a-zA-Z0-9_]+))?\s+WHERE\s+(.+?)\)\s+(?:AS\s+)?([a-zA-Z0-9_]+)/is', $selectCols, $subMatches)) {
                foreach ($subMatches[0] as $i => $fullMatch) {
                    $subSelect = trim($subMatches[1][$i]);
                    $subTable = $subMatches[2][$i];
                    $subTableAlias = $subMatches[3][$i] ?: $subTable;
                    $subWhereFull = $subMatches[4][$i];
                    $subAlias = $subMatches[5][$i];
                    
                    // Split subWhere into the actual WHERE and optional ORDER BY/LIMIT
                    $subOrderBy = null; $subLimit = null; $subWhere = $subWhereFull;
                    if (preg_match('/(.+?)\s+ORDER\s+BY\s+(.+?)(\s+LIMIT\s+(.+))?$/is', $subWhereFull, $wm)) {
                        $subWhere = $wm[1];
                        $subOrderBy = $wm[2];
                        $subLimit = $wm[4] ?? null;
                    } elseif (preg_match('/(.+?)\s+LIMIT\s+(.+)$/is', $subWhereFull, $wm)) {
                        $subWhere = $wm[1];
                        $subLimit = $wm[2];
                    }

                    $subData = $this->store->readTable($subTable);
                    
                    foreach ($this->results as &$row) {
                        $matches = [];
                        foreach ($subData as $subRow) {
                            if ($this->matchRowAcross($row, $subRow, $subWhere, $mainAlias, $subTableAlias)) $matches[] = $subRow;
                        }
                        
                        // Apply subquery sorting/limiting
                        if ($subOrderBy && !empty($matches)) {
                            $sortParts = explode(',', $subOrderBy);
                            $sortCol = trim(explode(' ', trim($sortParts[0]))[0], '` ');
                            $dir = stripos($sortParts[0], 'DESC') !== false ? -1 : 1;
                            usort($matches, function($a, $b) use ($sortCol, $dir) { return (($a[$sortCol] ?? '') <=> ($b[$sortCol] ?? '')) * $dir; });
                        }
                        if ($subLimit) $matches = array_slice($matches, 0, intval($subLimit));

                        // Resolve value
                        if ($subSelect === 'COUNT(*)') {
                            $row[$subAlias] = count($matches);
                        } else {
                            // Extract specific column or entire first row
                            $col = (strpos($subSelect, '(') !== false || $subSelect === '*') ? null : (strpos($subSelect, '.') !== false ? explode('.', $subSelect)[1] : $subSelect);
                            $row[$subAlias] = !empty($matches) ? ($col ? ($matches[0][$col] ?? null) : $matches[0]) : null;
                        }
                    }
                }
            }

            // Detect GROUP BY
            $groupBy = null;
            if (preg_match('/\s+GROUP\s+BY\s+([a-zA-Z0-9_.]+)/i', $sql, $gm)) {
                $groupBy = trim($gm[1], '` ');
                if (strpos($groupBy, '.') !== false) $groupBy = explode('.', $groupBy)[1];
            }

            // Handle Aggregates (Improved)
            $aggregates = [];
            if (preg_match_all('/(COUNT|MAX|SUM|AVG|MIN)\(\s*(DISTINCT\s+)?([a-zA-Z0-9_.*]+)\s*\)\s+(?:AS\s+)?([a-zA-Z0-9_]+)/i', $selectCols, $am)) {
                foreach ($am[0] as $i => $full) {
                    $aggregates[] = [
                        'func' => strtoupper($am[1][$i]),
                        'distinct' => !empty($am[2][$i]),
                        'col' => trim($am[3][$i], '` '),
                        'alias' => $am[4][$i]
                    ];
                }
            }

            if (!empty($aggregates)) {
                if ($groupBy) {
                    $groups = [];
                    foreach ($this->results as $row) {
                        $key = $row[$groupBy] ?? 'null';
                        if (!isset($groups[$key])) $groups[$key] = [];
                        $groups[$key][] = $row;
                    }
                    $this->results = [];
                    foreach ($groups as $key => $rows) {
                        $newRow = [$groupBy => $key];
                        foreach ($aggregates as $agg) {
                            $newRow[$agg['alias']] = $this->applyAggregate($rows, $agg);
                        }
                        // Also copy other non-aggregated columns from the first row of group
                        if (!empty($rows)) {
                            foreach ($rows[0] as $rk => $rv) {
                                if (!isset($newRow[$rk])) $newRow[$rk] = $rv;
                            }
                        }
                        $this->results[] = $newRow;
                    }
                } else {
                    // Global aggregate
                    $newRow = [];
                    // Special case for medicine_master_data streamed count
                    if ($mainTable === 'medicine_master_data' && !$where && count($aggregates) === 1 && $aggregates[0]['func'] === 'COUNT') {
                        $newRow[$aggregates[0]['alias']] = $this->store->countStreamed($mainTable, $aggregates[0]['distinct'] ? $aggregates[0]['col'] : null);
                    } else {
                        foreach ($aggregates as $agg) {
                            $newRow[$agg['alias']] = $this->applyAggregate($this->results, $agg);
                        }
                    }
                    $this->results = [$newRow];
                }
            } elseif (strtoupper($selectCols) === 'COUNT(*)') {
                $this->results = [['total' => ($mainTable === 'medicine_master_data' && !$where) ? $this->store->countStreamed($mainTable) : count($this->results)]];
            }

            // Handle ORDER BY
            if ($orderBy && !empty($this->results)) {
                $this->sortResults($orderBy);
            }

            // Handle Column Aliases and Selection (Final Pass)
            if ($selectCols !== '*' && !empty($this->results)) {
                $finalResults = [];
                $colMaps = [];
                // Complex parser for "table.col as alias" or "(SELECT...) as alias"
                // We've already handled subqueries, so we just need to make sure their aliases are in colMaps
                if (isset($subMatches[5])) {
                    foreach ($subMatches[5] as $sa) $colMaps[$sa] = $sa;
                }

                preg_match_all('/(?:[a-zA-Z0-9_.*]+|\(SELECT.+?\))\s+(?:AS\s+)?([a-zA-Z0-9_]+)/is', $selectCols, $cm);
                foreach ($cm[1] as $alias) {
                    $colMaps[$alias] = $alias;
                }

                // If no aliases found but specific columns requested
                if (empty($colMaps)) {
                    $parts = explode(',', $selectCols);
                    foreach ($parts as $p) {
                        $p = trim($p, '` ');
                        if (strpos($p, '.') !== false) $p = explode('.', $p)[1];
                        $colMaps[$p] = $p;
                    }
                }

                foreach ($this->results as $row) {
                    $newRow = [];
                    foreach ($colMaps as $alias => $rawCol) {
                        $val = $row[$alias] ?? ($row[$rawCol] ?? ($row[str_replace('`', '', $rawCol)] ?? null));
                        // Fallback for mobile -> phone
                        if ($val === null && ($alias === 'mobile' || $rawCol === 'mobile')) {
                            $val = $row['phone'] ?? null;
                        }
                        $newRow[$alias] = $val;
                    }
                    $finalResults[] = $newRow;
                }
                $this->results = $finalResults;
            }

            // Handle LIMIT
            if ($limit) {
                if (preg_match('/(:[a-zA-Z0-9_]+|[\d]+)\s*,\s*(:[a-zA-Z0-9_]+|[\d]+)/', $limit, $lm)) {
                    $off = $this->getLimitVal($lm[1]);
                    $cnt = $this->getLimitVal($lm[2]);
                    $this->results = array_slice($this->results, $off, $cnt);
                } else {
                    $cnt = $this->getLimitVal($limit);
                    $this->results = array_slice($this->results, 0, $cnt);
                }
            }

            $this->rowCount = count($this->results);
            return true;
        }
        
        // Standard INSERT/UPDATE/DELETE
        if (preg_match('/^INSERT\s+INTO\s+[`]?([a-zA-Z0-9_]+)[`]?\s*\((.+?)\)\s*VALUES\s*\((.+?)\)/i', $sql, $matches)) {
            $table = $matches[1];
            $cols = array_map('trim', explode(',', $matches[2]));
            $valParts = array_map('trim', explode(',', $matches[3]));
            
            $newRow = [];
            foreach ($cols as $i => $col) {
                $col = trim($col, '` ');
                $vStr = $valParts[$i];
                if (strpos($vStr, ':') === 0) {
                    $newRow[$col] = $this->boundParams[$vStr] ?? ($this->boundParams[ltrim($vStr, ':')] ?? null);
                } elseif (strtoupper($vStr) === 'NOW()') {
                    $newRow[$col] = date('Y-m-d H:i:s');
                } else {
                    $newRow[$col] = trim($vStr, "'\" ");
                }
            }

            $data = $this->store->readTable($table);
            
            $idCol = "{$table}_id";
            if ($table === 'users') $idCol = 'user_id';
            $singularMap = ['appointments' => 'appointment_id', 'patients' => 'patient_id', 'doctors' => 'doctor_id', 'prescriptions' => 'prescription_id', 'medicines' => 'medicine_id'];
            if (isset($singularMap[$table])) $idCol = $singularMap[$table];
            
            if (!empty($data) && !isset($data[0][$idCol])) {
                $firstRow = $data[0];
                if (isset($firstRow['id'])) $idCol = 'id';
                elseif (isset($firstRow["{$table}_id"])) $idCol = "{$table}_id";
            }

            $maxId = 0;
            foreach ($data as $row) {
                if (isset($row[$idCol]) && is_numeric($row[$idCol]) && $row[$idCol] > $maxId) $maxId = intval($row[$idCol]);
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

        if (preg_match('/^UPDATE\s+[`]?([a-zA-Z0-9_]+)[`]?(\s+[a-zA-Z0-9_]+)?\s+SET\s+(.+?)(\s+WHERE\s+(.+))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $setStr = $matches[3];
            $where = isset($matches[5]) ? $matches[5] : null;

            $data = $this->store->readTable($table);
            $updateCount = 0;
            
            $updates = [];
            // Improved update parsing to handle literals and functions
            preg_match_all('/([a-zA-Z0-9_`.]+)\s*=\s*(:[a-zA-Z0-9_]+|NOW\(\)|[\'"].*?[\'"]|[\d.]+)/i', $setStr, $updateMatches);
            foreach ($updateMatches[1] as $i => $col) {
                $col = trim(explode('.', trim($col, '` '))[1] ?? trim($col, '` '));
                $val = $updateMatches[2][$i];
                if (strpos($val, ':') === 0) {
                    $updates[$col] = $this->boundParams[$val] ?? ($this->boundParams[ltrim($val, ':')] ?? null);
                } elseif (strtoupper($val) === 'NOW()') {
                    $updates[$col] = date('Y-m-d H:i:s');
                } else {
                    $updates[$col] = trim($val, "'\" ");
                }
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

        if (preg_match('/^DELETE\s+FROM\s+[`]?([a-zA-Z0-9_]+)[`]?(\s+[a-zA-Z0-9_]+)?(\s+WHERE\s+(.+))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $where = isset($matches[4]) ? $matches[4] : null;
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

    private function getLimitVal($val) {
        $val = trim($val);
        if (strpos($val, ':') === 0) return intval($this->boundParams[$val] ?? ($this->boundParams[ltrim($val, ':')] ?? 0));
        return intval($val);
    }

    private function mergeRows($row1, $row2, $alias1, $alias2) {
        $merged = array_merge($row1, $row2);
        // Add aliased columns if needed
        foreach ($row1 as $k => $v) $merged["$alias1.$k"] = $v;
        foreach ($row2 as $k => $v) $merged["$alias2.$k"] = $v;
        
        // Specially handle users/doctors aliases for the users page
        if (($alias1 === 'u' || $alias1 === 'users') && ($alias2 === 'd' || $alias2 === 'doctors')) {
            $merged['doctor_first'] = $row2['first_name'] ?? null;
            $merged['doctor_last'] = $row2['last_name'] ?? null;
        }
        return $merged;
    }

    private function matchComplexJoin($row1, $row2, $on, $alias1, $alias2) {
        return $this->matchRowAcross($row1, $row2, $on, $alias1, $alias2);
    }

    private function matchRowAcross($rowMain, $rowSub, $on, $aliasMain = null, $aliasSub = null) {
        // Resolve placeholders first
        foreach ($this->boundParams as $k => $v) {
            $ph = strpos($k, ':') === 0 ? $k : ":$k";
            $val = is_numeric($v) ? $v : "'$v'";
            $on = str_replace($ph, $val, $on);
        }

        $on = trim($on, '() ');
        $parts = preg_split('/\s+OR\s+/i', $on);
        foreach ($parts as $part) {
            $andMatch = true;
            $subParts = preg_split('/\s+AND\s+/i', trim($part, '() '));
            foreach ($subParts as $sp) {
                if (preg_match('/([a-zA-Z0-9_.]+)\s*(=|!=|<|>)\s*([a-zA-Z0-9_.\'":]+)/i', $sp, $m)) {
                    $v1 = $this->getValForAlias($rowMain, $rowSub, $m[1], $aliasMain, $aliasSub);
                    $op = $m[2];
                    $v2 = $this->getValForAlias($rowMain, $rowSub, $m[3], $aliasMain, $aliasSub);
                    
                    if ($op === '=') { if ($v1 != $v2) $andMatch = false; }
                    elseif ($op === '!=') { if ($v1 == $v2) $andMatch = false; }
                    elseif ($op === '<') { if ($v1 >= $v2) $andMatch = false; }
                    elseif ($op === '>') { if ($v1 <= $v2) $andMatch = false; }
                }
                if (!$andMatch) break;
            }
            if ($andMatch) return true;
        }
        return false;
    }

    private function getValForAlias($row1, $row2, $col, $alias1, $alias2) {
        $col = trim($col, '` ');
        if (strpos($col, "'") === 0 || strpos($col, '"') === 0) return trim($col, "'\"");
        if (is_numeric($col)) return $col;
        if (strpos($col, ':') === 0) return $this->boundParams[$col] ?? ($this->boundParams[ltrim($col, ':')] ?? null);
        
        if (strpos($col, '.') !== false) {
            list($a, $c) = explode('.', $col);
            if ($a == $alias1) return $row1[$c] ?? null;
            if ($a == $alias2) return $row2[$c] ?? null;
            // Fallback: if the alias matches the table name in the rows (sometimes happens in joins)
            return $row2[$c] ?? ($row1[$c] ?? null);
        }
        return $row1[$col] ?? ($row2[$col] ?? null);
    }

    private function matchRow($row, $where) {
        if (!$where || $where === '1=1' || $where === '1') return true;
        
        // Handle DATE(p.admitted_at) = CURDATE()
        $where = preg_replace('/CURDATE\(\)/i', "'".date('Y-m-d')."'", $where);

        $parts = preg_split('/\s+OR\s+/i', trim($where, '() '));
        foreach ($parts as $part) {
            $andMatch = true;
            $subParts = preg_split('/\s+AND\s+/i', trim($part, '() '));
            foreach ($subParts as $sp) {
                $sp = trim($sp);
                if ($sp === '1=1' || $sp === '1') continue;

                $match = false;
                if (preg_match('/([a-zA-Z0-9_`.]+)\s+LIKE\s+(:[a-zA-Z0-9_]+|[\'"].*?[\'"])/i', $sp, $m)) {
                    $col = trim(strpos($m[1], '.') !== false ? explode('.', $m[1])[1] : $m[1], '` ');
                    $val = strtolower($this->boundParams[$m[2]] ?? ($this->boundParams[ltrim($m[2], ':')] ?? trim($m[2], "'\"")));
                    $val = trim($val, '%');
                    if (isset($row[$col]) && stripos($row[$col], $val) !== false) $match = true;
                } elseif (preg_match('/(DATE\()?([a-zA-Z0-9_`.]+)\)?\s*(=|!=|<|>)\s*(:[a-zA-Z0-9_]+|[\d.]+|[\'"].*?[\'"])/i', $sp, $m)) {
                    $isDate = !empty($m[1]);
                    $col = trim(strpos($m[2], '.') !== false ? explode('.', $m[2])[1] : $m[2], '` ');
                    $op = $m[3];
                    $expected = $m[4];
                    
                    if (strpos($expected, ':') === 0) $expected = $this->boundParams[$expected] ?? ($this->boundParams[ltrim($expected, ':')] ?? null);
                    else $expected = trim($expected, " '\"");

                    $actual = $row[$col] ?? null;
                    if ($isDate && $actual) $actual = substr($actual, 0, 10);

                    if ($op === '=') { if ($actual == $expected) $match = true; }
                    elseif ($op === '!=') { if ($actual != $expected) $match = true; }
                    elseif ($op === '<') { if ($actual < $expected) $match = true; }
                    elseif ($op === '>') { if ($actual > $expected) $match = true; }
                } elseif (preg_match('/([a-zA-Z0-9_`.]+)\s+IS\s+(NOT\s+)?NULL/i', $sp, $m)) {
                    $col = trim(strpos($m[1], '.') !== false ? explode('.', $m[1])[1] : $m[1], '` ');
                    $not = !empty($m[2]);
                    $isNull = (!isset($row[$col]) || $row[$col] === null);
                    if ($not) { if (!$isNull) $match = true; }
                    else { if ($isNull) $match = true; }
                }

                if (!$match) {
                    $andMatch = false;
                    break;
                }
            }
            if ($andMatch) return true;
        }
        return false;
    }

    private function sortResults($orderBy) {
        $parts = explode(',', $orderBy);
        $sortCol = trim(explode(' ', trim($parts[0]))[0], '` ');
        $dir = stripos($parts[0], 'DESC') !== false ? -1 : 1;
        
        usort($this->results, function($a, $b) use ($sortCol, $dir) {
            $v1 = $a[$sortCol] ?? '';
            $v2 = $b[$sortCol] ?? '';
            return ($v1 <=> $v2) * $dir;
        });
    }

    private function filterData($data, $where) {
        $filtered = [];
        foreach ($data as $row) {
            if ($this->matchRow($row, $where)) $filtered[] = $row;
        }
        return $filtered;
    }

    private function applyAggregate($rows, $agg) {
        if (empty($rows)) return ($agg['func'] === 'COUNT') ? 0 : null;
        
        $vals = [];
        $col = $agg['col'];
        foreach ($rows as $r) {
            if ($col === '*') $vals[] = 1;
            elseif (isset($r[$col])) $vals[] = $r[$col];
        }
        
        if ($agg['distinct']) $vals = array_unique($vals);
        
        switch ($agg['func']) {
            case 'COUNT': return count($vals);
            case 'MAX': return !empty($vals) ? max($vals) : null;
            case 'MIN': return !empty($vals) ? min($vals) : null;
            case 'SUM': return array_sum($vals);
            case 'AVG': return !empty($vals) ? array_sum($vals) / count($vals) : 0;
        }
        return null;
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
