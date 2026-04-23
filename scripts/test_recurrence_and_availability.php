<?php
/**
 * Test script: insert a simple recurrence rule (if table exists) and call
 * the availability endpoint for the chosen doctor/date to verify slots.
 * Run from CLI: php -f scripts/test_recurrence_and_availability.php
 */
require_once __DIR__ . '/../config/config.php';

try {
    // Ensure recurrence_rules table exists
    $chk = $db->prepare("SELECT 1 FROM recurrence_rules LIMIT 1");
    $chk->execute();
} catch (Exception $e) {
    echo "ERROR: recurrence_rules table not found or DB error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// pick a doctor and patient
$dq = $db->prepare('SELECT doctor_id FROM doctors LIMIT 1'); $dq->execute(); $doc = $dq->fetch(PDO::FETCH_ASSOC);
$pq = $db->prepare('SELECT patient_id FROM patients LIMIT 1'); $pq->execute(); $pat = $pq->fetch(PDO::FETCH_ASSOC);

if (empty($doc) || empty($pat)) {
    echo "ERROR: need at least one doctor and one patient in DB to run test.\n";
    exit(1);
}

$doctor_id = $doc['doctor_id'];
$patient_id = $pat['patient_id'];
$start_date = date('Y-m-d', strtotime('+1 day'));
$appt_time = '09:00:00';

// try to insert a test recurrence rule
try {
    $ins = $db->prepare('INSERT INTO recurrence_rules (doctor_id, patient_id, frequency, `interval`, start_date, appointment_time, duration_minutes, created_by, created_at, active) VALUES (:did, :pid, :freq, :intv, :sdate, :atime, :dur, :created_by, NOW(), 1)');
    $freq = 'weekly'; $intv = 1; $dur = 15; $created_by = $_SESSION['user_id'] ?? null;
    $ins->bindParam(':did', $doctor_id); $ins->bindParam(':pid', $patient_id);
    $ins->bindParam(':freq', $freq); $ins->bindParam(':intv', $intv);
    $ins->bindParam(':sdate', $start_date); $ins->bindParam(':atime', $appt_time);
    $ins->bindParam(':dur', $dur); $ins->bindParam(':created_by', $created_by);
    $ins->execute();
    $rid = $db->lastInsertId();
    echo "Inserted recurrence_id={$rid} for doctor={$doctor_id}, patient={$patient_id}, date={$start_date} time={$appt_time}\n";
} catch (Exception $e) {
    echo "ERROR inserting recurrence: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Call availability endpoint via CLI by setting $_GET and including the file in a separate php -r invocation
$ajaxPath = str_replace('\\','/', __DIR__ . '/../ajax/check_availability.php');
$phpCode = '$_GET["doctor_id"]=' . intval($doctor_id) . '; $_GET["start_date"]="' . $start_date . '"; $_GET["end_date"]="' . $start_date . '"; $_GET["duration"]=15; include "' . addslashes($ajaxPath) . '";';

echo "Calling availability endpoint for doctor {$doctor_id} on {$start_date}...\n";
$cmd = 'php -r ' . escapeshellarg($phpCode);
$out = shell_exec($cmd);
if ($out === null) {
    echo "ERROR: availability command failed to run.\n";
    exit(1);
}

echo "Availability output:\n";
echo $out . PHP_EOL;

echo "Test complete.\n";

?>
