<?php
require_once 'config/config.php';
function check($table) {
    global $db;
    echo "\nTable: $table\n";
    $q = $db->query("DESCRIBE $table");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
}
check('appointments');
check('consultation_history');
check('prescriptions');
?>
