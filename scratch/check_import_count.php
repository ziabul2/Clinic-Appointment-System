<?php
require_once 'config/config.php';
echo $db->query("SELECT COUNT(*) FROM medicine_master_data")->fetchColumn();
?>
