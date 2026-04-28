<?php
require_once 'config/config.php';
try {
    $q = $db->query("DESCRIBE consultation_history");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
