<?php
require_once 'config/config.php';
try {
    // MariaDB uses 'CHECK (json_valid(`additional_symptoms`))' which is often an anonymous constraint or named after the column in older versions.
    // In newer versions we can use the CONSTRAINT name if we know it. 
    // Looking at the SHOW CREATE output, it says: CHECK (json_valid(`additional_symptoms`))
    // We can try to ALTER TABLE to remove it. 
    // However, the easiest way to remove a CHECK constraint if we don't know the name is to redefine the column.
    
    $db->exec("ALTER TABLE consultation_history MODIFY COLUMN additional_symptoms LONGTEXT");
    echo "Successfully removed JSON constraint from additional_symptoms.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
