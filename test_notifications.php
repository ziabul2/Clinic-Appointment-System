<?php
/**
 * Test Notifications & Email System
 * This script creates a test appointment and sends notification emails
 */

require_once 'config/config.php';

if (!isLoggedIn()) {
    die('Please login first');
}

echo "=== NOTIFICATION SYSTEM TEST ===\n\n";

// Get latest appointment
$q = $db->prepare('SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                          p.first_name as patient_first_name, p.last_name as patient_last_name, p.email as patient_email,
                          d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.email as doctor_email
                   FROM appointments a
                   LEFT JOIN patients p ON a.patient_id = p.patient_id
                   LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                   ORDER BY a.appointment_id DESC LIMIT 1');
$q->execute();
$appointment = $q->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    die('No appointments found. Please create an appointment first.');
}

echo "Testing with appointment: #" . $appointment['appointment_id'] . "\n";
echo "Patient: " . $appointment['patient_first_name'] . " " . $appointment['patient_last_name'] . " (" . $appointment['patient_email'] . ")\n";
echo "Doctor: " . $appointment['doctor_first_name'] . " " . $appointment['doctor_last_name'] . " (" . $appointment['doctor_email'] . ")\n";
echo "Date & Time: " . $appointment['appointment_date'] . " " . $appointment['appointment_time'] . "\n\n";

// Test 1: Create notification in database
echo "TEST 1: Creating test notification in database...\n";
try {
    $uid = $_SESSION['user_id'];
    $title = "Test Notification";
    $message = "This is a test notification for appointment #" . $appointment['appointment_id'];
    $type = "appointment_created";
    $meta = json_encode(['appointment_id' => $appointment['appointment_id'], 'test' => true]);
    
    $ins = $db->prepare('INSERT INTO notifications (user_id, type, title, message, meta, created_at) VALUES (:user_id, :type, :title, :message, :meta, NOW())');
    $ins->bindParam(':user_id', $uid);
    $ins->bindParam(':type', $type);
    $ins->bindParam(':title', $title);
    $ins->bindParam(':message', $message);
    $ins->bindParam(':meta', $meta);
    $ins->execute();
    
    $notif_id = $db->lastInsertId();
    echo "✓ Notification created successfully (ID: $notif_id)\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Fetch notifications
echo "TEST 2: Fetching notifications from database...\n";
try {
    $fetch = $db->prepare('SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5');
    $fetch->bindParam(':user_id', $uid);
    $fetch->execute();
    $notifs = $fetch->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($notifs) . " recent notifications:\n";
    foreach ($notifs as $n) {
        $status = $n['is_read'] ? 'Read' : 'Unread';
        echo "  - " . $n['title'] . " ($status) - " . $n['created_at'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Send test email
echo "TEST 3: Sending test appointment confirmation email...\n";
try {
    $patient_email = $appointment['patient_email'];
    $doctor_email = $appointment['doctor_email'];
    $subject = "Appointment Confirmation - " . $appointment['appointment_date'];
    
    if (empty($patient_email)) {
        echo "⚠ Patient email is empty, skipping patient email\n";
    } else {
        $body = "Dear " . htmlspecialchars($appointment['patient_first_name']) . ",\n\n";
        $body .= "Your appointment with Dr. " . htmlspecialchars($appointment['doctor_first_name'] . " " . $appointment['doctor_last_name']) . " is confirmed.\n\n";
        $body .= "Date & Time: " . $appointment['appointment_date'] . " " . $appointment['appointment_time'] . "\n";
        $body .= "Appointment ID: " . $appointment['appointment_id'] . "\n\n";
        $body .= "Please arrive 10 minutes early.\n\n";
        $body .= "Best regards,\n" . SITE_NAME;
        
        $result = sendSMTPMail($patient_email, $subject, $body, "text/plain");
        if ($result) {
            echo "✓ Email sent to patient: $patient_email\n";
        } else {
            echo "✗ Failed to send email to patient\n";
        }
    }
    
    if (empty($doctor_email)) {
        echo "⚠ Doctor email is empty, skipping doctor notification email\n";
    } else {
        $doc_body = "Dr. " . htmlspecialchars($appointment['doctor_first_name']) . ",\n\n";
        $doc_body .= "You have a scheduled appointment.\n\n";
        $doc_body .= "Patient: " . htmlspecialchars($appointment['patient_first_name'] . " " . $appointment['patient_last_name']) . "\n";
        $doc_body .= "Date & Time: " . $appointment['appointment_date'] . " " . $appointment['appointment_time'] . "\n";
        $doc_body .= "Appointment ID: " . $appointment['appointment_id'] . "\n\n";
        $doc_body .= "Best regards,\n" . SITE_NAME;
        
        $result = sendSMTPMail($doctor_email, $subject, $doc_body, "text/plain");
        if ($result) {
            echo "✓ Email sent to doctor: $doctor_email\n";
        } else {
            echo "✗ Failed to send email to doctor\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Display notification dropdown preview
echo "TEST 4: Notification Dropdown Preview (JSON API response)...\n";
try {
    $fetch = $db->prepare('SELECT id, type, title, message, is_read, created_at FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10');
    $fetch->bindParam(':user_id', $uid);
    $fetch->execute();
    $notifs = $fetch->fetchAll(PDO::FETCH_ASSOC);
    
    $response = ['ok' => true, 'notifications' => $notifs];
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
echo "Next: Open the application in your browser and check:\n";
echo "1. Notification bell in header shows unread count\n";
echo "2. Click bell to view dropdown with recent notifications\n";
echo "3. Visit /pages/notifications.php for full notification page\n";
?>