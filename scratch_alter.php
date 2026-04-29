<?php
require 'config/config.php';
global $db;
try {
    $db->exec("ALTER TABLE users ADD COLUMN about VARCHAR(255) DEFAULT 'Available'");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
