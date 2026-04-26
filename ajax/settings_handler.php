<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$settings_file = __DIR__ . '/../private/user_settings.json';

// Load existing settings
$settings_data = [];
if (file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    $settings_data = json_decode($content, true) ?: [];
}

if ($action === 'save') {
    if (!verify_csrf()) {
        echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $setting_name = $_POST['name'] ?? '';
    $setting_value = $_POST['value'] ?? '';

    if (empty($setting_name)) {
        echo json_encode(['ok' => false, 'message' => 'Missing setting name']);
        exit;
    }

    // Update setting for the current user
    if (!isset($settings_data[$user_id])) {
        $settings_data[$user_id] = [];
    }
    
    // Sanitize value (convert string true/false to boolean if applicable)
    if ($setting_value === 'true') $setting_value = true;
    if ($setting_value === 'false') $setting_value = false;

    $settings_data[$user_id][$setting_name] = $setting_value;

    // Save back to file
    if (file_put_contents($settings_file, json_encode($settings_data, JSON_PRETTY_PRINT))) {
        echo json_encode(['ok' => true, 'message' => 'Setting saved']);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Failed to save setting']);
    }
} elseif ($action === 'load') {
    $user_settings = $settings_data[$user_id] ?? [];
    echo json_encode(['ok' => true, 'settings' => $user_settings]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Invalid action']);
}
