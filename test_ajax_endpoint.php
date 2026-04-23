<?php
$date = date('Y-m-d', strtotime('+1 day'));
// Find next Monday, Wednesday, or Friday
for ($i = 0; $i < 14; $i++) {
    $checkDate = date('Y-m-d', strtotime("+$i day"));
    $dayName = date('l', strtotime($checkDate));
    if (in_array($dayName, ['Monday', 'Wednesday', 'Friday'])) {
        $date = $checkDate;
        break;
    }
}

$url = "http://localhost/clinicapp/ajax/check_availability.php?doctor_id=1&start_date=" . urlencode($date) . "&end_date=" . urlencode($date) . "&duration=15&step=15";
echo "Testing URL: $url\n\n";

$context = stream_context_create([
    'http' => [
        'ignore_errors' => true
    ]
]);

$response = file_get_contents($url, false, $context);
echo "HTTP Response Headers:\n";
if (isset($http_response_header)) {
    foreach ($http_response_header as $header) {
        echo "  $header\n";
    }
}
echo "\nResponse Body:\n";
echo $response . "\n";

$json = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "\nJSON Decode Error: " . json_last_error_msg() . "\n";
} else {
    echo "\nDecoded JSON:\n";
    print_r($json);
}
?>
