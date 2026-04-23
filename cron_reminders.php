<?php
// cron_reminders.php
// Run this script periodically (e.g., every 15 minutes) via Task Scheduler or cron.
// It finds appointments scheduled in ~24 hours and ~1 hour and sends reminder emails to patient and doctor.

require_once __DIR__ . '/config/config.php';

try {
    $now = new DateTime();

    // Appointments roughly 24 hours from now (between 23.5 and 24.5 hours)
    $query24 = "SELECT a.*, p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, d.first_name AS d_first, d.last_name AS d_last, d.email AS d_email
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE a.status = 'scheduled' AND
                TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(a.appointment_date, ' ', a.appointment_time)) BETWEEN 1410 AND 1470"; // 23.5h to 24.5h

    $stmt = $db->prepare($query24);
    $stmt->execute();
    $appts24 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appts24 as $a) {
        $patientName = trim($a['p_first'] . ' ' . $a['p_last']);
        $doctorName = trim($a['d_first'] . ' ' . $a['d_last']);
        // send reminder
        $body = '<p>Dear ' . htmlspecialchars($patientName) . ',</p>';
        $body .= '<p>This is a reminder of your upcoming appointment with Dr. ' . htmlspecialchars($doctorName) . '.</p>';
        $body .= '<p><strong>Date:</strong> ' . htmlspecialchars($a['appointment_date']) . ' <strong>Time:</strong> ' . htmlspecialchars($a['appointment_time']) . '</p>';
        $body .= '<p>Please arrive 10 minutes early. If you need to cancel, contact us.</p>';
        sendSMTPMail($a['p_email'], SITE_NAME . ' - Appointment Reminder', $body, true, false);

        // notify doctor
        $dbody = '<p>Dear Dr. ' . htmlspecialchars($doctorName) . ',</p>';
        $dbody .= '<p>Reminder: You have an appointment scheduled with ' . htmlspecialchars($patientName) . ' on ' . htmlspecialchars($a['appointment_date']) . ' at ' . htmlspecialchars($a['appointment_time']) . '.</p>';
        sendSMTPMail($a['d_email'], SITE_NAME . ' - Upcoming Appointment', $dbody, true, false);

        // Create DB notifications for doctor user (if exists) and for admin/receptionist users
        try {
            $meta = ['appointment_id' => intval($a['appointment_id']), 'reminder_type' => '24h'];
            // avoid duplicate reminders recently created for same appointment
            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE type = 'appointment_reminder' AND JSON_EXTRACT(meta, '$.appointment_id') = :aid AND created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)");
            $chk->bindParam(':aid', $a['appointment_id']);
            $chk->execute();
            $rc = $chk->fetch(PDO::FETCH_ASSOC);
            if (intval($rc['cnt'] ?? 0) === 0) {
                // doctor user
                $uq = $db->prepare('SELECT user_id FROM users WHERE doctor_id = :did LIMIT 1');
                $uq->bindParam(':did', $a['doctor_id']);
                $uq->execute();
                if ($uq && $uq->rowCount() > 0) {
                    $ur = $uq->fetch(PDO::FETCH_ASSOC);
                    createNotification($db, $ur['user_id'], 'appointment_reminder', 'Appointment Reminder (24h)', "Appointment with $patientName on {$a['appointment_date']} at {$a['appointment_time']}", $meta);
                }
                // notify patient user if linked (by users.patient_id or by email)
                try {
                    $patientUserId = null;
                    try {
                        $pu = $db->prepare('SELECT user_id FROM users WHERE patient_id = :pid LIMIT 1');
                        $pu->bindParam(':pid', $a['patient_id']);
                        $pu->execute();
                        if ($pu->rowCount() > 0) {
                            $pr = $pu->fetch(PDO::FETCH_ASSOC);
                            $patientUserId = $pr['user_id'];
                        }
                    } catch (Exception $inner) {
                        if (!empty($a['p_email'])) {
                            $pu = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
                            $pu->bindParam(':email', $a['p_email']);
                            $pu->execute();
                            if ($pu->rowCount() > 0) {
                                $pr = $pu->fetch(PDO::FETCH_ASSOC);
                                $patientUserId = $pr['user_id'];
                            }
                        }
                    }
                    if ($patientUserId) {
                        createNotification($db, $patientUserId, 'appointment_reminder', 'Appointment Reminder (24h)', "Your appointment with Dr. $doctorName on {$a['appointment_date']} at {$a['appointment_time']} is in 24 hours.", $meta);
                    }
                } catch (Exception $e) {
                    logAction('CRON_NOTIF_ERROR', 'Patient notification (24h) error: ' . $e->getMessage());
                }
                // notify admin/receptionist users
                $aq = $db->query("SELECT user_id FROM users WHERE role IN ('admin','receptionist')");
                if ($aq) {
                    foreach ($aq->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        createNotification($db, $row['user_id'], 'appointment_reminder', 'Appointment Reminder (24h)', "Appointment for $patientName with Dr. $doctorName on {$a['appointment_date']} at {$a['appointment_time']}", $meta);
                    }
                }
            }
        } catch (Exception $e) {
            logAction('CRON_NOTIF_ERROR', $e->getMessage());
        }

        logAction('APPOINTMENT_REMINDER_SENT', "Appointment ID: {$a['appointment_id']} reminders sent (24h)");
    }

    // Appointments roughly 1 hour from now (between 30 and 90 minutes)
    $query1 = "SELECT a.*, p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, d.first_name AS d_first, d.last_name AS d_last, d.email AS d_email
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE a.status = 'scheduled' AND
                TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(a.appointment_date, ' ', a.appointment_time)) BETWEEN 30 AND 90";

    $stmt = $db->prepare($query1);
    $stmt->execute();
    $appts1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appts1 as $a) {
        $patientName = trim($a['p_first'] . ' ' . $a['p_last']);
        $doctorName = trim($a['d_first'] . ' ' . $a['d_last']);
        $body = '<p>Dear ' . htmlspecialchars($patientName) . ',</p>';
        $body .= '<p>This is a short reminder: your appointment with Dr. ' . htmlspecialchars($doctorName) . ' is in about 1 hour.</p>';
        $body .= '<p><strong>Date:</strong> ' . htmlspecialchars($a['appointment_date']) . ' <strong>Time:</strong> ' . htmlspecialchars($a['appointment_time']) . '</p>';
        sendSMTPMail($a['p_email'], SITE_NAME . ' - Appointment Reminder (1 hour)', $body, true, false);

        $dbody = '<p>Dear Dr. ' . htmlspecialchars($doctorName) . ',</p>';
        $dbody .= '<p>Short reminder: appointment with ' . htmlspecialchars($patientName) . ' is in about 1 hour.</p>';
        sendSMTPMail($a['d_email'], SITE_NAME . ' - Upcoming Appointment (1 hour)', $dbody, true, false);

        // Create DB notifications for doctor user (if exists) and for admin/receptionist users
        try {
            $meta = ['appointment_id' => intval($a['appointment_id']), 'reminder_type' => '1h'];
            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE type = 'appointment_reminder' AND JSON_EXTRACT(meta, '$.appointment_id') = :aid AND created_at > DATE_SUB(NOW(), INTERVAL 3 HOUR)");
            $chk->bindParam(':aid', $a['appointment_id']);
            $chk->execute();
            $rc = $chk->fetch(PDO::FETCH_ASSOC);
            if (intval($rc['cnt'] ?? 0) === 0) {
                $uq = $db->prepare('SELECT user_id FROM users WHERE doctor_id = :did LIMIT 1');
                $uq->bindParam(':did', $a['doctor_id']);
                $uq->execute();
                if ($uq && $uq->rowCount() > 0) {
                    $ur = $uq->fetch(PDO::FETCH_ASSOC);
                    createNotification($db, $ur['user_id'], 'appointment_reminder', 'Appointment Reminder (1h)', "Appointment with $patientName on {$a['appointment_date']} at {$a['appointment_time']}", $meta);
                }
                // notify patient user if linked (by users.patient_id or by email)
                try {
                    $patientUserId = null;
                    try {
                        $pu = $db->prepare('SELECT user_id FROM users WHERE patient_id = :pid LIMIT 1');
                        $pu->bindParam(':pid', $a['patient_id']);
                        $pu->execute();
                        if ($pu->rowCount() > 0) {
                            $pr = $pu->fetch(PDO::FETCH_ASSOC);
                            $patientUserId = $pr['user_id'];
                        }
                    } catch (Exception $inner) {
                        if (!empty($a['p_email'])) {
                            $pu = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
                            $pu->bindParam(':email', $a['p_email']);
                            $pu->execute();
                            if ($pu->rowCount() > 0) {
                                $pr = $pu->fetch(PDO::FETCH_ASSOC);
                                $patientUserId = $pr['user_id'];
                            }
                        }
                    }
                    if ($patientUserId) {
                        createNotification($db, $patientUserId, 'appointment_reminder', 'Appointment Reminder (1h)', "Your appointment with Dr. $doctorName on {$a['appointment_date']} at {$a['appointment_time']} is in about 1 hour.", $meta);
                    }
                } catch (Exception $e) {
                    logAction('CRON_NOTIF_ERROR', 'Patient notification (1h) error: ' . $e->getMessage());
                }
                $aq = $db->query("SELECT user_id FROM users WHERE role IN ('admin','receptionist')");
                if ($aq) {
                    foreach ($aq->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        createNotification($db, $row['user_id'], 'appointment_reminder', 'Appointment Reminder (1h)', "Appointment for $patientName with Dr. $doctorName on {$a['appointment_date']} at {$a['appointment_time']}", $meta);
                    }
                }
            }
        } catch (Exception $e) {
            logAction('CRON_NOTIF_ERROR', $e->getMessage());
        }

        logAction('APPOINTMENT_REMINDER_SENT', "Appointment ID: {$a['appointment_id']} reminders sent (1h)");
    }

} catch (Exception $e) {
    logAction('CRON_REMINDERS_ERROR', $e->getMessage());
}

?>