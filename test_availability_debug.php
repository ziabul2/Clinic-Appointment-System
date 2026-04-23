<?php
require_once __DIR__ . '/config/config.php';

// Test if appointment table and cache table exist
try {
    // Check appointments table
    $q1 = $db->query('DESCRIBE appointments');
    $appt_cols = $q1->fetchAll();
    echo "✓ Appointments table exists with " . count($appt_cols) . " columns\n";
    
    // Check availability_cache table
    $q2 = $db->query('DESCRIBE availability_cache');
    $cache_cols = $q2->fetchAll();
    echo "✓ Availability cache table exists with " . count($cache_cols) . " columns\n";
    
    // Check recurrence_rules table
    $q3 = $db->query('DESCRIBE recurrence_rules');
    $rec_cols = $q3->fetchAll();
    echo "✓ Recurrence rules table exists with " . count($rec_cols) . " columns\n";
    
    // Check if there's a doctor_id = 1
    $dq = $db->query('SELECT COUNT(*) as cnt FROM doctors');
    $dc = $dq->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total doctors: " . $dc['cnt'] . "\n";
    
    // Check appointments
    $aq = $db->query('SELECT COUNT(*) as cnt FROM appointments');
    $ac = $aq->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total appointments: " . $ac['cnt'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
