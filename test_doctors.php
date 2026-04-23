<?php
require_once 'config/config.php';

try {
    // Get first doctor
    $stmt = $db->query("SELECT doctor_id, first_name, last_name, available_days, available_time_start, available_time_end FROM doctors LIMIT 1");
    $doctor = $stmt->fetch();
    
    if ($doctor) {
        echo "Found doctor: " . $doctor['doctor_id'] . " - " . $doctor['first_name'] . " " . $doctor['last_name'] . PHP_EOL;
        echo "Available days: " . $doctor['available_days'] . PHP_EOL;
        echo "Available time: " . $doctor['available_time_start'] . " - " . $doctor['available_time_end'] . PHP_EOL;
    } else {
        echo "No doctors found" . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
?>
