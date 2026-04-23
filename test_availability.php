<?php
require_once 'config/config.php';

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query("SHOW TABLES LIKE 'availability_cache'");
    $result = $stmt->fetchAll();
    
    echo 'Tables found: ' . count($result) . PHP_EOL;
    if(count($result) > 0) {
        echo 'availability_cache EXISTS' . PHP_EOL;
        
        // Check its structure
        $col_stmt = $db->prepare('DESCRIBE availability_cache');
        $col_stmt->execute();
        $cols = $col_stmt->fetchAll();
        echo 'Columns:' . PHP_EOL;
        foreach($cols as $col) {
            echo '  - ' . $col['Field'] . ' (' . $col['Type'] . ')' . PHP_EOL;
        }
    } else {
        echo 'availability_cache DOES NOT EXIST' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
?>
