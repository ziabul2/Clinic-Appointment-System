<?php
require_once 'config/config.php';
try {
    $sql = "ALTER TABLE consultation_history 
            ADD COLUMN appointment_id INT(11) AFTER doctor_id,
            ADD COLUMN bp VARCHAR(20) AFTER notes,
            ADD COLUMN pulse VARCHAR(20) AFTER bp,
            ADD COLUMN weight VARCHAR(20) AFTER pulse,
            ADD COLUMN temperature VARCHAR(20) AFTER weight,
            ADD COLUMN spo2 VARCHAR(20) AFTER temperature,
            ADD COLUMN diagnosis TEXT AFTER spo2,
            ADD COLUMN treatment_plan TEXT AFTER diagnosis,
            ADD COLUMN prescription_id INT(11) AFTER treatment_plan,
            ADD INDEX (appointment_id),
            ADD INDEX (prescription_id)";
    $db->exec($sql);
    echo "Schema updated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
