<?php
/**
 * Helper functions: mailer, UI helpers, additional utilities
 */

// Basic sendMail using PHP mail() as a fallback. For production, integrate PHPMailer/SMTP.
function sendMail($to, $subject, $htmlBody, $plainBody = '') {
    $from = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;

    // If PHPMailer is available via Composer autoload, use it for SMTP
    // Added version check because current composer.lock requires PHP 8.1+
    if (version_compare(PHP_VERSION, '8.1.0', '>=') && file_exists(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                // SMTP config if provided
                if (!empty(MAIL_SMTP_HOST)) {
                    $mail->isSMTP();
                    $mail->Host = MAIL_SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = MAIL_SMTP_USER;
                    $mail->Password = MAIL_SMTP_PASS;
                    $mail->SMTPSecure = MAIL_SMTP_SECURE;
                    $mail->Port = MAIL_SMTP_PORT;
                }
                $mail->setFrom($from, $fromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                if (!empty($plainBody)) $mail->AltBody = $plainBody;
                $mail->send();
                return true;
            }
        } catch (Throwable $e) {
            $log_dir = __DIR__ . '/../logs/';
            if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
            $msg = '['.date('Y-m-d H:i:s').'] [MAIL_LOAD_ERROR] '.$e->getMessage().PHP_EOL;
            file_put_contents($log_dir.'errors.log', $msg, FILE_APPEND | LOCK_EX);
            if (defined('MAIL_FORCE_SMTP') && MAIL_FORCE_SMTP) return false;
        }
    }

    // Fallback to PHP mail()
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $fromName <$from>" . "\r\n";

    $result = false;
    try {
        $result = mail($to, $subject, $htmlBody, $headers);
        if (!$result) {
            $log_dir = __DIR__ . '/../logs/';
            if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
            $msg = '['.date('Y-m-d H:i:s').'] [EMAIL_ERROR] Failed to send email to: '.$to.' Subject: '.$subject.PHP_EOL;
            file_put_contents($log_dir.'errors.log', $msg, FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        $log_dir = __DIR__ . '/../logs/';
        if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
        $msg = '['.date('Y-m-d H:i:s').'] [EMAIL_EXCEPTION] '.$e->getMessage().PHP_EOL;
        file_put_contents($log_dir.'errors.log', $msg, FILE_APPEND | LOCK_EX);
    }

    return $result;
}

// CSRF helpers
function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($token).'">';
}

function verify_csrf() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        return false;
    }
    return true;
}

function sendUserCreationEmail($email, $username, $password) {
    $subject = SITE_NAME . ' - Your account has been created';
    $body  = '<p>Hello '.htmlspecialchars($username).',</p>';
    $body .= '<p>An account has been created for you on '.SITE_NAME.'.</p>';
    $body .= '<p><strong>Username:</strong> '.htmlspecialchars($username).'<br>';
    $body .= '<strong>Password:</strong> '.htmlspecialchars($password).'</p>';
    $body .= '<p>Please change your password after your first login.</p>';
    $body .= '<p>Login: <a href="'.SITE_URL.'">'.SITE_URL.'</a></p>';
    $body .= '<p>Regards,<br>'.SITE_NAME.'</p>';

    return sendMail($email, $subject, $body);
}

function sendPasswordResetEmail($email, $username) {
    $subject = SITE_NAME . ' - Your password has been reset';
    $body  = '<p>Hello '.htmlspecialchars($username).',</p>';
    $body .= '<p>Your account password was recently reset by an administrator. If you did not request this, contact the administrator immediately.</p>';
    $body .= '<p>Login: <a href="'.SITE_URL.'">'.SITE_URL.'</a></p>';
    $body .= '<p>Regards,<br>'.SITE_NAME.'</p>';

    return sendMail($email, $subject, $body);
}

// Send a password reset link (tokenized) to user
function sendPasswordResetLink($email, $username, $token) {
    $subject = SITE_NAME . ' - Reset your password';
    $resetUrl = rtrim(SITE_URL, '/') . '/pages/password_reset.php?token=' . urlencode($token);
    $body  = '<p>Hello '.htmlspecialchars($username).',</p>';
    $body .= '<p>We received a request to reset your account password. Click the link below to set a new password. This link will expire in 1 hour.</p>';
    $body .= '<p><a href="'.htmlspecialchars($resetUrl).'">Reset your password</a></p>';
    $body .= '<p>If you did not request a password reset, you can safely ignore this email.</p>';
    $body .= '<p>Regards,<br>'.SITE_NAME.'</p>';

    return sendMail($email, $subject, $body);
}

// Helper to expose server-side flash messages to client JS (returns JSON-safe array)
function popFlashMessages() {
    $out = ['success' => null, 'error' => null];
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['success'])) { $out['success'] = $_SESSION['success']; unset($_SESSION['success']); }
    if (!empty($_SESSION['error'])) { $out['error'] = $_SESSION['error']; unset($_SESSION['error']); }
    return $out;
}

/**
 * sendSMTPMail: Uses PHPMailer if available (Composer). Falls back to PHP mail() when not available.
 * $debug: if true, sets PHPMailer debug level to 2 and routes debug output to error_log.
 */
function sendSMTPMail($to, $subject, $body, $isHtml = true, $debug = false) {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (version_compare(PHP_VERSION, '8.1.0', '>=') && file_exists($vendor)) {
        try {
            require_once $vendor;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                // Use namespaced PHPMailer classes
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                // Debug
                if ($debug) {
                    $mail->SMTPDebug = 2; // verbose
                    $mail->Debugoutput = function($str, $level) {
                        error_log("PHPMailer debug level $level; message: $str");
                    };
                }

                $mail->isSMTP();
                $mail->Host       = MAIL_SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_SMTP_USER;
                $mail->Password   = MAIL_SMTP_PASS;
                if (defined('MAIL_SMTP_SECURE') && MAIL_SMTP_SECURE === 'ssl') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Port       = MAIL_SMTP_PORT;

                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($to);

                $mail->isHTML($isHtml);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                if (!$isHtml) $mail->AltBody = strip_tags($body);

                $mail->send();
                return true;
            }
        } catch (Throwable $e) {
            error_log('PHPMailer load/send exception: ' . ($e->getMessage() ?? 'unknown'));
            return false;
        }
    }

    // If MAIL_FORCE_SMTP is enabled and PHPMailer is not available, do not fallback
    if (defined('MAIL_FORCE_SMTP') && MAIL_FORCE_SMTP) {
        error_log('sendSMTPMail: PHPMailer not available and MAIL_FORCE_SMTP is true. Not falling back to mail().');
        return false;
    }

    // Fallback: try PHP mail()
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">" . "\r\n";
    try {
        $res = mail($to, $subject, $body, $headers);
        if (!$res) error_log("PHP mail() failed to send to $to");
        return $res;
    } catch (Exception $e) {
        error_log('PHP mail exception: ' . $e->getMessage());
        return false;
    }
}

function sendAppointmentNotificationToPatient($patientEmail, $patientName, $doctorName, $date, $time, $notes='') {
    $subject = SITE_NAME . ' - Appointment Confirmation';
    $body  = '<p>Hello '.htmlspecialchars($patientName).',</p>';
    $body .= '<p>Your appointment has been scheduled with <strong>'.htmlspecialchars($doctorName).'</strong>.</p>';
    $body .= '<p><strong>Date:</strong> '.htmlspecialchars($date).' <strong>Time:</strong> '.htmlspecialchars($time).'</p>';
    if ($notes) $body .= '<p><strong>Notes:</strong> '.nl2br(htmlspecialchars($notes)).'</p>';
    $body .= '<p>Regards,<br>'.SITE_NAME.'</p>';

    // Use sendMailReport to get detailed result for UI
    $res = sendMailReport($patientEmail, $subject, $body, true);
    return $res;
}



function sendAppointmentNotificationToDoctor($doctorEmail, $doctorName, $patientName, $date, $time, $notes='') {
    $subject = SITE_NAME . ' - New Appointment Booked';
    $body  = '<p>Hello Dr. '.htmlspecialchars($doctorName).',</p>';
    $body .= '<p>A new appointment has been booked.</p>';
    $body .= '<p><strong>Patient:</strong> '.htmlspecialchars($patientName).'<br>';
    $body .= '<strong>Date:</strong> '.htmlspecialchars($date).' <strong>Time:</strong> '.htmlspecialchars($time).'</p>';
    if ($notes) $body .= '<p><strong>Notes:</strong> '.nl2br(htmlspecialchars($notes)).'</p>';
    $body .= '<p>Regards,<br>'.SITE_NAME.'</p>';

    // Use sendMailReport to get detailed result for UI
    $res = sendMailReport($doctorEmail, $subject, $body, true);
    return $res;
}

/**
 * sendMailReport: like sendMail but returns structured result with error message when available
 * returns array: ['ok' => bool, 'error' => null|string]
 */
function sendMailReport($to, $subject, $htmlBody, $isHtml = true) {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    // Try PHPMailer first if available
    if (version_compare(PHP_VERSION, '8.1.0', '>=') && file_exists($vendor)) {
        try {
            require_once $vendor;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                if (!empty(MAIL_SMTP_HOST)) {
                    $mail->isSMTP();
                    $mail->Host = MAIL_SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = MAIL_SMTP_USER;
                    $mail->Password = MAIL_SMTP_PASS;
                    $mail->SMTPSecure = MAIL_SMTP_SECURE;
                    $mail->Port = MAIL_SMTP_PORT;
                }
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($to);
                $mail->isHTML($isHtml);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                if (!$isHtml) $mail->AltBody = strip_tags($htmlBody);
                $mail->send();
                return ['ok' => true, 'error' => null];
            }
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $log_dir = __DIR__ . '/../logs/'; if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
            file_put_contents($log_dir.'errors.log', '['.date('c').'] [MAIL_REPORT_LOAD_ERROR] '.$msg.PHP_EOL, FILE_APPEND | LOCK_EX);
            if (defined('MAIL_FORCE_SMTP') && MAIL_FORCE_SMTP) {
                return ['ok' => false, 'error' => 'SMTP load error: ' . $msg];
            }
        }
    }

    // Fallback to PHP mail()
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">" . "\r\n";
    try {
        $res = mail($to, $subject, $htmlBody, $headers);
        if (!$res) {
            $log_dir = __DIR__ . '/../logs/'; if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
            $msg = '['.date('c').'] [MAIL_FALLBACK_ERROR] PHP mail() failed to send to: '.$to.PHP_EOL;
            file_put_contents($log_dir.'errors.log', $msg, FILE_APPEND | LOCK_EX);
            return ['ok' => false, 'error' => 'PHP mail() failed to send message.'];
        }
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        $log_dir = __DIR__ . '/../logs/'; if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
        file_put_contents($log_dir.'errors.log', '['.date('c').'] [MAIL_EXCEPTION] '.$e->getMessage().PHP_EOL, FILE_APPEND | LOCK_EX);
        return ['ok' => false, 'error' => 'Mail exception: ' . $e->getMessage()];
    }
}

/**
 * Notifications helpers
 */
function createNotification($db, $user_id, $type, $title, $message, $meta = null) {
    try {
        $ins = $db->prepare('INSERT INTO notifications (user_id, type, title, message, meta, created_at) VALUES (:uid, :type, :title, :message, :meta, NOW())');
        $metaJson = is_null($meta) ? null : json_encode($meta);
        $ins->bindParam(':uid', $user_id);
        $ins->bindParam(':type', $type);
        $ins->bindParam(':title', $title);
        $ins->bindParam(':message', $message);
        $ins->bindParam(':meta', $metaJson);
        $ins->execute();
        return $db->lastInsertId();
    } catch (Exception $e) {
        // log but don't throw
        logAction('NOTIF_CREATE_ERROR', $e->getMessage());
        return false;
    }
}

function getNotifications($db, $user_id, $limit = 20) {
    try {
        $q = $db->prepare('SELECT id, type, title, message, meta, is_read, created_at FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim');
        $q->bindParam(':uid', $user_id);
        $q->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logAction('NOTIF_FETCH_ERROR', $e->getMessage());
        return [];
    }
}

function getUnreadNotificationCount($db, $user_id) {
    try {
        $q = $db->prepare('SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :uid AND is_read = 0');
        $q->bindParam(':uid', $user_id);
        $q->execute();
        $r = $q->fetch(PDO::FETCH_ASSOC);
        return intval($r['cnt'] ?? 0);
    } catch (Exception $e) {
        logAction('NOTIF_COUNT_ERROR', $e->getMessage());
        return 0;
    }
}

// Small UI helper: return a fade-in wrapper class
function pageFadeIn() {
    return 'fade-in';
}

// Show a 404 page programmatically
function show_404($message = '') {
    if (!headers_sent()) http_response_code(404);
    if ($message) {
        $_SESSION['error'] = $message;
    }
    require_once __DIR__ . '/../pages/404.php';
    exit;
}

// Show a 403 page programmatically
function show_403($message = '') {
    if (!headers_sent()) http_response_code(403);
    if ($message) {
        $_SESSION['error'] = $message;
    }
    require_once __DIR__ . '/../pages/403.php';
    exit;
}

?>
