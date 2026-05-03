<?php
/**
 * Medicine Search Cache & Indexing System
 * Updated to use SQLite as the source of truth.
 */

class MedicineCache {
    private $basePath;
    private $cachePath;
    private $indexFile;
    private $partitionPath;
    private $db;

    public function __construct($basePath, $db = null) {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->cachePath = $this->basePath . 'cache' . DIRECTORY_SEPARATOR;
        $this->indexFile = $this->cachePath . 'medicine_search_index.json';
        $this->partitionPath = $this->cachePath . 'partitions' . DIRECTORY_SEPARATOR;
        $this->db = $db; // HybridPDO instance

        if (!is_dir($this->cachePath)) mkdir($this->cachePath, 0777, true);
        if (!is_dir($this->partitionPath)) mkdir($this->partitionPath, 0777, true);
    }

    /**
     * Rebuilds the search index from the SQLite database.
     * Extracts only essential fields and partitions data by first letter.
     */
    public function rebuildIndex() {
        if (!$this->db) return false;

        echo "Rebuilding medicine index from SQLite...\n";
        
        // Increase memory limit for indexing
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        try {
            // Use a cursor-like fetch to be memory efficient
            $stmt = $this->db->prepare("SELECT id, brand_name, generic_name, dosage_form, strength FROM medicine_master_data");
            $stmt->execute();

            $partitions = [];
            $fullIndex = [];
            $count = 0;

            while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = [
                    'id' => $record['id'],
                    'b' => $record['brand_name'],
                    'g' => $record['generic_name'],
                    'f' => $record['dosage_form'] ?? '',
                    's' => $record['strength'] ?? ''
                ];

                $fullIndex[] = $entry;

                // Partition by first letter of Brand Name
                $brand = $record['brand_name'] ?: '';
                $char = strtolower(substr($brand, 0, 1));
                if (!preg_match('/[a-z0-9]/', $char)) $char = 'other';
                
                if (!isset($partitions[$char])) $partitions[$char] = [];
                $partitions[$char][] = $entry;
                
                $count++;
                if ($count % 5000 === 0) echo "  ...processed $count records\n";
            }

            // Save partitions
            foreach ($partitions as $char => $data) {
                file_put_contents($this->partitionPath . "{$char}.json", json_encode($data));
            }

            // Save a compact full index
            file_put_contents($this->indexFile, json_encode($fullIndex));

            echo "Index rebuilt successfully. Total records: $count\n";
            return true;
        } catch (Exception $e) {
            error_log("Index Rebuild Error: " . $e->getMessage());
            return false;
        }
    }

     /**
     * Performs a super fast search using partitions (SQLite fallback).
     * Note: Server-side search is now preferred via HybridPDO/SQLite.
     */
    public function search($query, $limit = 30) {
        $query = strtolower(trim($query));
        if (empty($query)) return [];

        $char = substr($query, 0, 1);
        if (!preg_match('/[a-z0-9]/', $char)) $char = 'other';

        $partitionFile = $this->partitionPath . "{$char}.json";
        $data = [];

        if (file_exists($partitionFile)) {
            $data = json_decode(file_get_contents($partitionFile), true) ?: [];
        } elseif (file_exists($this->indexFile)) {
            $data = json_decode(file_get_contents($this->indexFile), true) ?: [];
        }

        if (empty($data)) return [];

        $results = [];
        foreach ($data as $item) {
            if (stripos($item['b'], $query) !== false || stripos($item['g'], $query) !== false) {
                $results[] = [
                    'id' => $item['id'],
                    'brand_name' => $item['b'],
                    'generic_name' => $item['g'],
                    'dosage_form' => $item['f'],
                    'strength' => $item['s'],
                    'type' => 'cached'
                ];
                if (count($results) >= $limit) break;
            }
        }

        return $results;
    }

    public function isIndexReady() {
        return file_exists($this->indexFile);
    }
}
?>
