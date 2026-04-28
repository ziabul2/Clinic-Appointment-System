<?php
require_once 'config/config.php';
try {
    $db->exec("DROP TABLE IF EXISTS medicine_master_data");
    $sql = "CREATE TABLE medicine_master_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        brand_name VARCHAR(255),
        generic_name VARCHAR(255),
        dosage_form VARCHAR(255),
        strength VARCHAR(255),
        manufacturer VARCHAR(255),
        package_container VARCHAR(255),
        package_size VARCHAR(255),
        type VARCHAR(100),
        slug VARCHAR(255),
        monograph_link TEXT,
        drug_class VARCHAR(255),
        indication TEXT,
        indication_description LONGTEXT,
        therapeutic_class_description LONGTEXT,
        pharmacology_description LONGTEXT,
        dosage_description LONGTEXT,
        administration_description LONGTEXT,
        interaction_description LONGTEXT,
        contraindications_description LONGTEXT,
        side_effects_description LONGTEXT,
        pregnancy_and_lactation_description LONGTEXT,
        precautions_description LONGTEXT,
        pediatric_usage_description LONGTEXT,
        overdose_effects_description LONGTEXT,
        storage_conditions_description LONGTEXT,
        meta_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "Table medicine_master_data created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
