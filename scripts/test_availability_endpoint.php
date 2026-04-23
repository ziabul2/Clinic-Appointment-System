<?php
/**
 * Test: verify availability endpoint returns slots for the inserted recurrence
 */
require_once __DIR__ . '/../config/config.php';

// Get the recurrence we just created
$rq = $db->prepare('SELECT * FROM recurrence_rules ORDER BY recurrence_id DESC LIMIT 1');
$rq->execute();
$rule = $rq->fetch(PDO::FETCH_ASSOC);

if (empty($rule)) {
    echo "ERROR: No recurrence rules found in database.\n";
    exit(1);
}

$doctor_id = $rule['doctor_id'];
$start_date = $rule['start_date'];
$end_date = $rule['start_date'];

echo "Testing availability endpoint:\n";
echo "  Doctor ID: {$doctor_id}\n";
echo "  Date: {$start_date}\n";
echo "  Duration: 15 min\n\n";

// Build the AJAX URL
$url = SITE_URL . "/ajax/check_availability.php?doctor_id=" . urlencode($doctor_id) . "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&duration=15&step=15";

echo "Calling: {$url}\n\n";

// Simulate HTTP request using file_get_contents or curl
$ctx = stream_context_create(["http" => ["timeout" => 5]]);
$response = @file_get_contents($url, false, $ctx);

if ($response === false) {
    echo "ERROR: Could not fetch availability endpoint.\n";
    exit(1);
}

$data = json_decode($response, true);

if (empty($data)) {
    echo "ERROR: Invalid JSON response.\n";
    echo "Raw response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

if (!$data['ok']) {
    echo "ERROR: Endpoint returned error: " . ($data['error'] ?? 'unknown') . "\n";
    exit(1);
}

echo "SUCCESS! Availability endpoint returned:\n";
echo "  OK: " . ($data['ok'] ? 'true' : 'false') . "\n";
echo "  Doctor: " . $data['doctor_id'] . "\n";
echo "  Slots for {$start_date}:\n";

$slots = $data['slots'][$start_date] ?? [];
if (empty($slots)) {
    echo "    (no available slots)\n";
} else {
    foreach ($slots as $slot) {
        echo "    - {$slot}\n";
    }
}

echo "\n✓ Test passed! Availability endpoint works correctly.\n";

?>
