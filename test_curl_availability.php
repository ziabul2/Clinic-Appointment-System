<?php
// Test the availability endpoint directly
$url = 'http://localhost/clinicapp/ajax/check_availability.php';
$params = [
    'doctor_id' => 1,
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'duration' => 15,
    'step' => 15
];

$full_url = $url . '?' . http_build_query($params);
echo "Testing URL: $full_url\n\n";

$response = @file_get_contents($full_url);
if ($response === false) {
    echo "Error: Could not fetch URL\n";
} else {
    echo "Response:\n";
    echo $response . "\n\n";
    
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Parsed JSON:\n";
        print_r($json);
    } else {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    }
}
?>
