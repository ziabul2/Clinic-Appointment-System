<?php
require_once 'config/config.php';
$q = $db->query("SHOW TABLES");
print_r($q->fetchAll(PDO::FETCH_COLUMN));
?>
