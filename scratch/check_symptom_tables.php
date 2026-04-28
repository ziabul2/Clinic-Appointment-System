<?php
require_once 'config/config.php';
function check($t) {
    global $db;
    echo "\nTable: $t\n";
    $q = $db->query("DESCRIBE $t");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
}
check('symptoms');
check('symptom_specialty_mapping');
?>
