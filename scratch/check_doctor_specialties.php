<?php
require_once 'config/config.php';
$q = $db->query("DESCRIBE doctor_specialties");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
