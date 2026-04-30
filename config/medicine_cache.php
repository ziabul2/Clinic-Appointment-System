<?php
/**
 * Medicine Search Cache & Indexing System
 * Designed for "Super Fast" searching of large medicine datasets.
 */

class MedicineCache {
    private $basePath;
    private $cachePath;
    private $indexFile;
    private $partitionPath;

    public function __construct($basePath) {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->cachePath = $this->basePath . 'cache' . DIRECTORY_SEPARATOR;
        $this->indexFile = $this->cachePath . 'medicine_search_index.json';
        $this->partitionPath = $this->cachePath . 'partitions' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->cachePath)) mkdir($this->cachePath, 0777, true);
        if (!is_dir($this->partitionPath)) mkdir($this->partitionPath, 0777, true);
    }

    /**
     * Rebuilds the search index from the master JSON file.
     * Extracts only essential fields and partitions data by first letter.
     */
    public function rebuildIndex() {
        $masterFile = $this->basePath . "clinic_management_table_medicine_master_data.json";
        if (!file_exists($masterFile)) return false;

        $handle = fopen($masterFile, 'r');
        if (!$handle) return false;

        $partitions = [];
        $fullIndex = [];
        
        // Increase memory limit for indexing if needed
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes max

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            $line = rtrim($line, ',');
            if (strpos($line, '{"id":') === 0) {
                $record = json_decode($line, true);
                if ($record) {
                    $entry = [
                        'id' => $record['id'],
                        'b' => $record['brand_name'], // Brand
                        'g' => $record['generic_name'], // Generic
                        'f' => $record['dosage_form'] ?? '', // Form
                        's' => $record['strength'] ?? '' // Strength
                    ];

                    $fullIndex[] = $entry;

                    // Partition by first letter of Brand Name
                    $char = strtolower(substr($record['brand_name'], 0, 1));
                    if (!preg_match('/[a-z0-9]/', $char)) $char = 'other';
                    
                    if (!isset($partitions[$char])) $partitions[$char] = [];
                    $partitions[$char][] = $entry;
                }
            }
        }
        fclose($handle);

        // Save partitions
        foreach ($partitions as $char => $data) {
            file_put_contents($this->partitionPath . "{$char}.json", json_encode($data));
        }

        // Save a compact full index for broader searches
        file_put_contents($this->indexFile, json_encode($fullIndex));

        return true;
    }

    /**
     * Performs a super fast search using partitions.
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
        } else {
            // Fallback to full index if partition missing
            if (file_exists($this->indexFile)) {
                $data = json_decode(file_get_contents($this->indexFile), true) ?: [];
            }
        }

        if (empty($data)) return [];

        $results = [];
        $count = 0;

        foreach ($data as $item) {
            // Match Brand or Generic
            if (stripos($item['b'], $query) !== false || stripos($item['g'], $query) !== false) {
                $results[] = [
                    'id' => $item['id'],
                    'brand_name' => $item['b'],
                    'generic_name' => $item['g'],
                    'dosage_form' => $item['f'],
                    'strength' => $item['s'],
                    'type' => 'cached'
                ];
                $count++;
                if ($count >= $limit) break;
            }
        }

        return $results;
    }

    public function isIndexReady() {
        return file_exists($this->indexFile);
    }
}
?>
