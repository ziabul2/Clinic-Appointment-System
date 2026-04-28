<?php
require_once __DIR__ . '/../config/config.php';
$table = 'medicine_master_data';
$stmt = $db->prepare("DESCRIBE $table");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    echo $col['Field'] . "\n";
}
