<?php
require_once 'config/config.php';
try {
    $db->exec("ALTER TABLE appointments ADD COLUMN temperature VARCHAR(20) AFTER weight, ADD COLUMN spo2 VARCHAR(20) AFTER temperature");
    echo "Successfully added temperature and spo2 to appointments table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
