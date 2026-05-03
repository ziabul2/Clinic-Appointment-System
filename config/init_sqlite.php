<?php
/**
 * SQLite Initialization Script v6
 * Converts MySQL schema to SQLite and populates the offline database.
 */

require_once __DIR__ . '/config.php';

// Increase limits for large data processing
set_time_limit(0);
ini_set('memory_limit', '2048M');

class SQLiteInitializer {
    private $mysqlFile;
    private $sqliteFile;
    private $sqlite;

    public function __construct($mysqlFile, $sqliteFile) {
        $this->mysqlFile = $mysqlFile;
        $this->sqliteFile = $sqliteFile;
    }

    public function run() {
        echo "Starting SQLite Initialization...\n";

        if (!file_exists($this->mysqlFile)) {
            throw new Exception("MySQL schema file not found: {$this->mysqlFile}");
        }

        $dbDir = dirname($this->sqliteFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $this->sqlite = new PDO("sqlite:" . $this->sqliteFile);
        $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Parse and Create Tables
        $this->createTables();

        // 3. Create Sync Table
        $this->createSyncTable();

        echo "SQLite database schema initialized.\n";
    }

    private function createTables() {
        echo "Parsing schema and creating tables...\n";
        
        $handle = fopen($this->mysqlFile, 'r');
        if (!$handle) throw new Exception("Could not open MySQL file.");

        $currentTable = "";
        $inTable = false;
        $tableName = "";

        while (($line = fgets($handle)) !== false) {
            $trimmedLine = trim($line);
            
            // Start of a CREATE TABLE
            if (preg_match('/^CREATE TABLE\s+`?([a-z0-9_]+)`?/i', $trimmedLine, $m)) {
                $inTable = true;
                $tableName = $m[1];
                $currentTable = $line;
                continue;
            }

            if ($inTable) {
                $currentTable .= $line;
                // End of CREATE TABLE - look for ) ENGINE=... ;
                if (preg_match('/\)\s*ENGINE\s*=.*?;/i', $trimmedLine) || preg_match('/\)\s*;/i', $trimmedLine)) {
                    $inTable = false;
                    $this->processTableBlock($tableName, $currentTable);
                    $currentTable = "";
                    $tableName = "";
                }
            }
        }
        fclose($handle);
    }

    private function processTableBlock($tableName, $sqlBlock) {
        $startPos = strpos($sqlBlock, '(');
        $endPos = strrpos($sqlBlock, ')');
        
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $definition = substr($sqlBlock, $startPos + 1, $endPos - $startPos - 1);
            $sqliteSql = $this->convertToSQLite($tableName, $definition);
            
            try {
                $this->sqlite->exec("DROP TABLE IF EXISTS `$tableName` ");
                $this->sqlite->exec($sqliteSql);
                echo "Table `$tableName` created.\n";
            } catch (Exception $e) {
                echo "Error creating table `$tableName`: " . $e->getMessage() . "\n";
                echo "--- GENERATED SQL START ---\n$sqliteSql\n--- GENERATED SQL END ---\n";
            }
        }
    }

    private function convertToSQLite($tableName, $definition) {
        $lines = explode("\n", $definition);
        $newLines = [];
        $primaryKey = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Strip MySQL comments
            $line = preg_replace('/--.*/', '', $line);
            $line = preg_replace('/\/\*.*?\*\//', '', $line);
            $line = trim($line);
            if (empty($line)) continue;

            // Handle Primary Key
            if (preg_match('/^PRIMARY KEY\s*\(`?(.*?)`?\)/i', $line, $m)) {
                $primaryKey = $m[1];
                continue;
            }
            
            // Remove other keys/constraints
            if (preg_match('/^(UNIQUE KEY|KEY|FULLTEXT KEY|CONSTRAINT|UNIQUE\s+INDEX|INDEX|FOREIGN\s+KEY)/i', $line)) {
                continue;
            }

            // Replace types correctly
            if (preg_match('/^(`?[a-z0-9_]+`?)\s+([a-z]+)(\(.*\))?(.*)$/i', $line, $m)) {
                $colName = $m[1];
                $type = strtoupper($m[2]);
                $rest = $m[4] ?? '';

                $newType = 'TEXT';
                if (in_array($type, ['INT', 'INTEGER', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT'])) {
                    $newType = 'INTEGER';
                } elseif (in_array($type, ['DECIMAL', 'DOUBLE', 'FLOAT', 'REAL'])) {
                    $newType = 'REAL';
                } elseif (in_array($type, ['TIMESTAMP', 'DATETIME', 'DATE'])) {
                    $newType = 'DATETIME';
                }
                
                // Clean up rest of the line
                $rest = preg_replace('/AUTO_INCREMENT/i', '', $rest);
                $rest = preg_replace('/ON UPDATE\s+current_timestamp(\(\))?/i', '', $rest);
                $rest = preg_replace('/CHARACTER SET\s+[a-z0-9]+/i', '', $rest);
                $rest = preg_replace('/COLLATE\s+[a-z0-9_]+/i', '', $rest);
                $rest = preg_replace('/unsigned/i', '', $rest);
                $rest = preg_replace('/current_timestamp\(\)/i', 'CURRENT_TIMESTAMP', $rest);
                $rest = preg_replace('/CHECK\s*\(.*\)/i', '', $rest);

                $rest = trim(rtrim(trim($rest), ','));
                $line = "$colName $newType $rest";
            } else {
                if ($line == ',') continue;
                $line = rtrim($line, ',');
            }

            if (!empty(trim($line))) {
                $newLines[] = trim($line);
            }
        }

        // Re-add primary key
        if ($primaryKey) {
            $pkHandled = false;
            foreach ($newLines as &$line) {
                if (preg_match("/^`$primaryKey` INTEGER/i", $line) || preg_match("/^$primaryKey INTEGER/i", $line)) {
                    $line .= " PRIMARY KEY AUTOINCREMENT";
                    $pkHandled = true;
                    break;
                }
            }
            if (!$pkHandled) {
                $newLines[] = "PRIMARY KEY (`$primaryKey`)";
            }
        }

        $sqliteSql = "CREATE TABLE `$tableName` (\n  " . implode(",\n  ", $newLines) . "\n);";
        return $sqliteSql;
    }

    private function createSyncTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `pending_sync` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `sync_id` TEXT NOT NULL,
            `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `query` TEXT NOT NULL,
            `params` TEXT,
            `table_name` TEXT
        )";
        $this->sqlite->exec($sql);
        echo "Sync table `pending_sync` created.\n";
    }

    public function importData($hybridDb) {
        echo "Importing live data from MySQL...\n";
        $pdo = $hybridDb->getPdo();
        if (!($pdo instanceof PDO)) {
            echo "Skipping data import: MySQL is offline or not configured correctly.\n";
            return;
        }

        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            echo "Error fetching tables from MySQL: " . $e->getMessage() . "\n";
            return;
        }

        $skip = ['ai_logs']; // We now include medicine_master_data

        foreach ($tables as $table) {
            if (in_array($table, $skip)) {
                echo "Skipping data for table `$table` (large).\n";
                continue;
            }

            try {
                $check = $this->sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
                if (!$check) {
                    echo "Table `$table` does not exist in SQLite, skipping import.\n";
                    continue;
                }

                echo "Importing data for table `$table`...\n";
                
                // Use a prepared statement for MySQL fetch to be memory efficient if possible
                // But PDO::query() with fetchAll() is fine for 2GB memory.
                // However, for medicine_master_data, let's use a cursor to be safe.
                
                $mysqlStmt = $pdo->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
                
                $this->sqlite->beginTransaction();
                $firstRow = $mysqlStmt->fetch();
                if (!$firstRow) {
                    $this->sqlite->commit();
                    echo "Table `$table` is empty.\n";
                    continue;
                }
                
                $cols = array_keys($firstRow);
                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $sql = "INSERT OR REPLACE INTO `$table` (`" . implode("`,`", $cols) . "`) VALUES ($placeholders)";
                $sqliteStmt = $this->sqlite->prepare($sql);

                // Execute first row
                $sqliteStmt->execute(array_values($firstRow));
                $count = 1;
                
                // Fetch remaining rows
                while ($row = $mysqlStmt->fetch()) {
                    $sqliteStmt->execute(array_values($row));
                    $count++;
                    if ($count % 5000 === 0) {
                        echo "  ...processed $count rows\n";
                    }
                }
                
                $this->sqlite->commit();
                echo "Imported $count rows for table `$table`.\n";
            } catch (Exception $e) {
                if ($this->sqlite->inTransaction()) $this->sqlite->rollBack();
                echo "Error importing data for table `$table`: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Check if run from CLI or requested
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    try {
        $mysqlFile = __DIR__ . '/../sqls_DB/clinic_management.sql';
        $sqliteFile = __DIR__ . '/../DatabaseSQL/clinic_offline.db';
        
        $init = new SQLiteInitializer($mysqlFile, $sqliteFile);
        $init->run();
        
        echo "Connecting to HybridPDO to fetch live data...\n";
        require_once __DIR__ . '/hybrid_db.php';
        $init->importData($db);
        
    } catch (Exception $e) {
        echo "FATAL ERROR: " . $e->getMessage() . "\n";
    }
}
