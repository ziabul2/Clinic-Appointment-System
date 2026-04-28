<?php
require_once 'config/config.php';
$q = $db->query("DESCRIBE medicine_master_data");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
