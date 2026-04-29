<?php
require 'config/config.php';
try {
    $stmt = $db->query('DESCRIBE users;');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
