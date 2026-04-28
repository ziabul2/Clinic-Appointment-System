<?php
require_once 'config/config.php';
try {
    echo "Adding indexes...\n";
    $db->exec("CREATE INDEX idx_brand ON medicine_master_data (brand_name)");
    $db->exec("CREATE INDEX idx_generic ON medicine_master_data (generic_name)");
    // Fulltext for faster indication searching
    $db->exec("CREATE FULLTEXT INDEX idx_indication_full ON medicine_master_data (brand_name, generic_name, indication)");
    echo "Indexes added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
