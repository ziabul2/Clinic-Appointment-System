<?php
require_once 'config/config.php';
echo "Symptoms count: " . $db->query("SELECT COUNT(*) FROM symptoms")->fetchColumn() . "\n";
echo "Mapping count: " . $db->query("SELECT COUNT(*) FROM symptom_specialty_mapping")->fetchColumn() . "\n";
?>
