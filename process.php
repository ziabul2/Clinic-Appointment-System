<?php
/**
 * Central Process Handler
 * Handles all form submissions and AJAX requests
 */

require_once 'config/config.php';

// Password reset token lifetime in minutes (adjustable)
if (!defined('RESET_TOKEN_LIFETIME_MINUTES')) define('RESET_TOKEN_LIFETIME_MINUTES', 30);

/**
 * Action handlers extracted to functions to keep switch compact.
 */
function handle_add_patient($db) {
    if (!$_POST) return;
    if (!verify_csrf()) {
        $_SESSION['error'] = 'Invalid request token (CSRF).';
        redirect('pages/patients.php');
    }
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $emergency_contact = sanitizeInput($_POST['emergency_contact']);
    $medical_history = sanitizeInput($_POST['medical_history']);

    $query = "INSERT INTO patients (first_name, last_name, email, phone, address, date_of_birth, gender, emergency_contact, medical_history) ";
    $query .= "VALUES (:first_name, :last_name, :email, :phone, :address, :date_of_birth, :gender, :emergency_contact, :medical_history)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':date_of_birth', $date_of_birth);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':emergency_contact', $emergency_contact);
    $stmt->bindParam(':medical_history', $medical_history);

    try {
        if ($stmt->execute()) {
            logAction("PATIENT_ADDED", "New patient: $first_name $last_name");
                    // If AJAX caller, return JSON indicating success and that the client should show button success state
                    // Detect AJAX (X-Requested-With or explicit application/json content-type)
                    $isAjax = (
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                    );
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'message' => 'Patient added successfully', 'patient_id' => $db->lastInsertId(), 'button_success' => true]);
                        exit;
                    }
                    $_SESSION['success'] = "Patient added successfully!";
        } else {
            throw new Exception("Failed to add patient");
        }
    } catch (Exception $e) {
        logAction('ADD_PATIENT_ERROR', $e->getMessage());
        $_SESSION['error'] = 'Failed to add patient.';
    }
    return;
}

// Check if action parameter is set
if (!isset($_GET['action'])) {
    logAction("PROCESS_ERROR", "No action specified");
    redirect('index.php');
}

// We no longer block actions when DB_OK is false because HybridPDO handles offline mode.

$action = $_GET['action'];

try {
    switch ($action) {
        case 'add_patient':
            if ($_POST) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/patients.php');
                }
                $first_name = sanitizeInput($_POST['first_name']);
                $last_name = sanitizeInput($_POST['last_name']);
                $email = sanitizeInput($_POST['email']);
                $phone = sanitizeInput($_POST['phone']);
                $address = sanitizeInput($_POST['address']);
                $date_of_birth = sanitizeInput($_POST['date_of_birth']);
                $gender = sanitizeInput($_POST['gender']);
                $emergency_contact = sanitizeInput($_POST['emergency_contact']);
                $medical_history = sanitizeInput($_POST['medical_history']);
                
                $query = "INSERT INTO patients (first_name, last_name, email, phone, address, date_of_birth, gender, emergency_contact, medical_history, admitted_at) 
                         VALUES (:first_name, :last_name, :email, :phone, :address, :date_of_birth, :gender, :emergency_contact, :medical_history, CURRENT_TIMESTAMP)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':date_of_birth', $date_of_birth);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':emergency_contact', $emergency_contact);
                $stmt->bindParam(':medical_history', $medical_history);
                
                if ($stmt->execute()) {
                    logAction("PATIENT_ADDED", "New patient: $first_name $last_name");
                    $_SESSION['success'] = "Patient added successfully!";
                    // Detect AJAX and return JSON redirect when appropriate
                    // Detect AJAX
                    $isAjax = (
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                    );
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'message' => 'Patient added successfully', 'patient_id' => $db->lastInsertId(), 'button_success' => true, 'redirect' => 'patients.php']);
                        exit;
                    }
                    // Create notifications for admin/receptionist users
                    try {
                        $title = 'New patient added';
                        $message = "Patient $first_name $last_name was added to the system.";
                        $meta = ['patient_id' => $db->lastInsertId() ?: null];
                        $aq = $db->query("SELECT user_id FROM users WHERE role IN ('admin','receptionist')");
                        if ($aq) {
                            foreach ($aq->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                createNotification($db, $row['user_id'], 'patient_created', $title, $message, $meta);
                            }
                        }
                    } catch (Exception $e) {
                        logAction('NOTIF_ERROR', 'Failed to create patient notifications: ' . $e->getMessage());
                    }
                } else {
                    throw new Exception("Failed to add patient");
                }
            }
            redirect('pages/patients.php');
            break;

        case 'readmit_patient':
            if (!isLoggedIn()) { redirect('index.php'); }
            $pid = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
            if ($pid) {
                $q = $db->prepare("UPDATE patients SET admitted_at = CURRENT_TIMESTAMP WHERE patient_id = :id");
                $q->bindParam(':id', $pid);
                if ($q->execute()) {
                    logAction("PATIENT_READMITTED", "Patient ID: $pid moved to today's list");
                    $_SESSION['success'] = "Patient re-admitted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to re-admit patient.";
                }
            }
            redirect('pages/patients.php');
            break;

        case 'archive_patient':
            if (!isLoggedIn()) { redirect('index.php'); }
            $pid = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
            if ($pid) {
                // Set admitted_at to yesterday to move out of today's list
                $q = $db->prepare("UPDATE patients SET admitted_at = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE patient_id = :id");
                $q->bindParam(':id', $pid);
                if ($q->execute()) {
                    logAction("PATIENT_ARCHIVED", "Patient ID: $pid manually archived");
                    $_SESSION['success'] = "Patient moved to archive.";
                } else {
                    $_SESSION['error'] = "Failed to archive patient.";
                }
            }
            redirect('pages/patients.php');
            break;
            
        case 'add_doctor':
            if ($_POST) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/doctors.php');
                }
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $specialization = sanitizeInput($_POST['specialization'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $license_number = sanitizeInput($_POST['license_number'] ?? '');
                $available_days = '';
                if (isset($_POST['available_days']) && is_array($_POST['available_days'])) {
                    $available_days = implode(',', array_map('sanitizeInput', $_POST['available_days']));
                }
                $consultation_fee = sanitizeInput($_POST['consultation_fee'] ?? '');
                $available_time_start = sanitizeInput($_POST['available_time_start'] ?? '');
                $available_time_end = sanitizeInput($_POST['available_time_end'] ?? '');

                // Handle profile picture upload if present
                $profile_filename = null;
                if (!empty($_FILES['profile_picture']['name'])) {
                    $upload_dir = __DIR__ . '/uploads/doctors/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $profile_filename = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $dest = $upload_dir . $profile_filename;
                    @move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest);
                }

                // Build insert query with optional profile_picture column
                $hasProfile = !empty($profile_filename);
                // Ensure the doctors table actually has a profile_picture column before including it
                try {
                    $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'profile_picture'");
                    $colChk->execute();
                    $rowChk = $colChk->fetch(PDO::FETCH_ASSOC);
                    $hasProfileColumn = !empty($rowChk) && intval($rowChk['cnt']) > 0;
                } catch (Exception $e) {
                    $hasProfileColumn = false;
                }
                // If the doctors table does not have profile_picture column, do not keep the uploaded file
                $hasProfile = $hasProfile && $hasProfileColumn;
                if (!empty($profile_filename) && !$hasProfileColumn) {
                    // remove the uploaded file to avoid orphaned files
                    $maybe = __DIR__ . '/uploads/doctors/' . $profile_filename;
                    if (file_exists($maybe)) {
                        @unlink($maybe);
                    }
                    $profile_filename = null;
                    $hasProfile = false;
                }
                $query = "INSERT INTO doctors (first_name, last_name, specialization, email, phone, license_number, available_days, consultation_fee, available_time_start, available_time_end" . ($hasProfile ? ", profile_picture" : "") . ") VALUES (:first_name, :last_name, :specialization, :email, :phone, :license_number, :available_days, :consultation_fee, :available_time_start, :available_time_end" . ($hasProfile ? ", :profile_picture" : "") . ")";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':specialization', $specialization);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':license_number', $license_number);
                $stmt->bindParam(':available_days', $available_days);
                $stmt->bindParam(':consultation_fee', $consultation_fee);
                $stmt->bindParam(':available_time_start', $available_time_start);
                $stmt->bindParam(':available_time_end', $available_time_end);
                if ($hasProfile) $stmt->bindParam(':profile_picture', $profile_filename);

                try {
                    if ($stmt->execute()) {
                        $doctor_id = $db->lastInsertId();

                        // If requested, create a linked user account for the doctor and send set-password link
                        if (!empty($_POST['create_user']) && !empty($email)) {
                            try {
                                // Check if email already exists in users table
                                $chkUser = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
                                $chkUser->bindParam(':email', $email);
                                $chkUser->execute();
                                if ($chkUser->rowCount() > 0) {
                                    $_SESSION['error'] = 'Email already registered as a user account. Please use a different email or uncheck "Create login account".';
                                    redirect('pages/doctors.php');
                                }
                                
                                $username = explode('@', $email)[0] . rand(10,99);
                                $random = bin2hex(random_bytes(8));
                                $hash = password_hash($random, PASSWORD_DEFAULT);
                                $uquery = 'INSERT INTO users (username, password, email, role, created_at, doctor_id) VALUES (:username, :password, :email, :role, NOW(), :doctor_id)';
                                $ustmt = $db->prepare($uquery);
                                $role = 'Doctor';
                                $ustmt->bindParam(':username', $username);
                                $ustmt->bindParam(':password', $hash);
                                $ustmt->bindParam(':email', $email);
                                $ustmt->bindParam(':role', $role);
                                $ustmt->bindParam(':doctor_id', $doctor_id);
                                $ustmt->execute();
                                $newUserId = $db->lastInsertId();

                                $token = bin2hex(random_bytes(32));
                                $expires = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_LIFETIME_MINUTES . ' minutes'));
                                $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                                $ins->bindParam(':user_id', $newUserId);
                                $ins->bindParam(':token', $token);
                                $ins->bindParam(':expires_at', $expires);
                                $ins->execute();

                                sendPasswordResetLink($email, $username, $token);
                            } catch (Exception $e) {
                                // Log but don't fail the main doctor creation
                                logAction('DOCTOR_USER_CREATE_ERROR', $e->getMessage());
                            }
                        }

                        logAction("DOCTOR_ADDED", "New doctor: Dr. $first_name $last_name (ID: $doctor_id)");
                        // Detect AJAX (X-Requested-With or Accept: application/json or ?ajax=1)
                        $isAjax = (
                            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                            (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                            (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                            (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                        );
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['ok' => true, 'message' => 'Doctor added successfully', 'doctor_id' => $doctor_id, 'toast' => true, 'redirect' => 'doctors.php']);
                            exit;
                        }
                        $_SESSION['success'] = "Doctor added successfully!";
                    } else {
                        throw new Exception('Failed to add doctor (unknown reason).');
                    }
                } catch (PDOException $e) {
                    logAction('DOCTOR_ADD_ERROR', $e->getMessage());
                    $errMsg = 'Error adding doctor: ' . (strpos($e->getMessage(), 'Duplicate') !== false ? 'Email already exists.' : 'Database error. Please try again.');
                    $isAjax = (
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                    );
                    if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['ok' => false, 'error' => $errMsg]);
                            exit;
                    }
                    $_SESSION['error'] = $errMsg;
                    redirect('pages/doctors.php');
                }
            }
            redirect('pages/doctors.php');
            break;
        
        case 'add_user':
            if ($_POST) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/users.php');
                }
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $password = isset($_POST['password']) ? trim($_POST['password']) : '';
                $doctor_id = isset($_POST['doctor_id']) && $_POST['doctor_id'] !== '' ? sanitizeInput($_POST['doctor_id']) : null;

                // Basic checks
                if (empty($username) || empty($password) || empty($role)) {
                    $_SESSION['error'] = 'Username, password and role are required.';
                    redirect('pages/users.php');
                }

                // Check username uniqueness
                $check = $db->prepare("SELECT user_id FROM users WHERE username = :username");
                $check->bindParam(':username', $username);
                $check->execute();
                if ($check->rowCount() > 0) {
                    $_SESSION['error'] = 'Username already exists.';
                    redirect('pages/users.php');
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, password, email, role, first_name, last_name, created_at" . (isset($doctor_id) ? ", doctor_id" : "") . ") VALUES (:username, :password, :email, :role, :first_name, :last_name, NOW()" . (isset($doctor_id) ? ", :doctor_id" : "") . ")";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hash);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                if (isset($doctor_id)) $stmt->bindParam(':doctor_id', $doctor_id);

                if ($stmt->execute()) {
                    $newId = $db->lastInsertId();
                    
                    // Create notifications for admin/receptionist users
                    try {
                        $title = 'New user added';
                        $message = "User $username ($role) was added to the system.";
                        $meta = ['user_id' => $newId];
                        $aq = $db->query("SELECT user_id FROM users WHERE role IN ('admin','receptionist')");
                        if ($aq) {
                            foreach ($aq->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                if ($row['user_id'] != ($_SESSION['user_id'] ?? 0)) {
                                    createNotification($db, $row['user_id'], 'user_created', $title, $message, $meta);
                                }
                            }
                        }
                    } catch (Exception $e) {
                        logAction('NOTIF_ERROR', 'Failed to create user notifications: ' . $e->getMessage());
                    }

                    logAction('USER_ADDED', "User $username (Role: $role) added by " . ($_SESSION['username'] ?? 'System'));
                    logAuth('USER_CREATED', $username, $newId);
                    // Detect AJAX and return JSON when appropriate
                    $isAjax = (
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                    );
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'message' => 'User created successfully', 'user_id' => $newId, 'toast' => true]);
                        exit;
                    }
                    $_SESSION['success'] = 'User created successfully.';

                    // Instead of emailing plaintext passwords, create a one-time set-password token and send link
                    if (!empty($email)) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_LIFETIME_MINUTES . ' minutes'));
                        $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                        $ins->bindParam(':user_id', $newId);
                        $ins->bindParam(':token', $token);
                        $ins->bindParam(':expires_at', $expires);
                        $ins->execute();

                        sendPasswordResetLink($email, $username, $token);
                    }
                } else {
                    throw new Exception('Failed to create user');
                }
            }
            redirect('pages/users.php');
            break;

        case 'link_patient_user':
            // Link or unlink a patient to a user account (admin/receptionist only)
            if ($_POST) {
                if (!verify_csrf()) { $_SESSION['error'] = 'Invalid request token (CSRF).'; redirect('pages/map_patients_users.php'); }
                checkRole(['admin','receptionist']);
                $patient_id = isset($_POST['patient_id']) ? sanitizeInput($_POST['patient_id']) : null;
                $user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? sanitizeInput($_POST['user_id']) : null;

                try {
                    // Ensure schema has patient_id column
                    $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'patient_id'");
                    $colChk->execute(); $rr = $colChk->fetch(PDO::FETCH_ASSOC);
                    if (empty($rr) || intval($rr['cnt']) === 0) {
                        $_SESSION['error'] = 'The users table does not have a patient_id column. Please add it first.';
                        redirect('pages/map_patients_users.php');
                    }

                    // Unlink any existing user currently linked to this patient
                    $up = $db->prepare('UPDATE users SET patient_id = NULL WHERE patient_id = :pid');
                    $up->bindParam(':pid', $patient_id);
                    $up->execute();

                    if ($user_id) {
                        // Assign this patient to the selected user
                        $assign = $db->prepare('UPDATE users SET patient_id = :pid WHERE user_id = :uid');
                        $assign->bindParam(':pid', $patient_id);
                        $assign->bindParam(':uid', $user_id);
                        $assign->execute();
                        $_SESSION['success'] = 'Patient linked to user successfully.';
                        logAction('MAP_PATIENT_USER', "Patient $patient_id linked to user $user_id by " . ($_SESSION['username'] ?? 'Unknown'));
                    } else {
                        $_SESSION['success'] = 'Patient unlinked from any user.';
                        logAction('UNLINK_PATIENT_USER', "Patient $patient_id unlinked by " . ($_SESSION['username'] ?? 'Unknown'));
                    }
                } catch (Exception $e) {
                    logAction('MAP_ERROR', $e->getMessage());
                    $_SESSION['error'] = 'Failed to link/unlink patient: ' . $e->getMessage();
                }
            }
            redirect('pages/map_patients_users.php');
            break;

        case 'update_user':
            if ($_POST) {
                if (!verify_csrf()) { $_SESSION['error'] = 'Invalid CSRF.'; redirect('pages/employees.php'); }
                checkRole(['admin','root']);
                $user_id = sanitizeInput($_POST['user_id']);
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $doctor_id = isset($_POST['doctor_id']) && $_POST['doctor_id'] !== '' ? sanitizeInput($_POST['doctor_id']) : null;

                $query = "UPDATE users SET username = :username, email = :email, role = :role, first_name = :first_name, last_name = :last_name, doctor_id = :doctor_id WHERE user_id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':doctor_id', $doctor_id);
                $stmt->bindParam(':id', $user_id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = 'User updated successfully.';
                    logAction('USER_UPDATED', "User $username updated by " . ($_SESSION['username'] ?? 'System'));
                } else {
                    $_SESSION['error'] = 'Failed to update user.';
                }
            }
            redirect('pages/employees.php');
            break;

        case 'delete_user':
            if (isset($_GET['id'])) {
                checkRole(['admin','root']);
                $user_id = sanitizeInput($_GET['id']);
                
                // Don't allow deleting yourself
                if ($user_id == ($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['error'] = 'You cannot delete your own account.';
                    redirect('pages/employees.php');
                }

                $stmt = $db->prepare("DELETE FROM users WHERE user_id = :id");
                $stmt->bindParam(':id', $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'User deleted successfully.';
                    logAction('USER_DELETED', "User ID $user_id deleted by " . ($_SESSION['username'] ?? 'System'));
                } else {
                    $_SESSION['error'] = 'Failed to delete user.';
                }
            }
            redirect('pages/employees.php');
            break;

        case 'apply_patient_column':
            // Add patient_id column to users table (admin only). Requires confirmation.
            if ($_POST) {
                if (!verify_csrf()) { $_SESSION['error'] = 'Invalid request token (CSRF).'; redirect('pages/map_patients_users.php'); }
                checkRole(['admin']);
                $confirm = isset($_POST['confirm']) ? sanitizeInput($_POST['confirm']) : null;
                if ($confirm !== '1') { $_SESSION['error'] = 'Please confirm schema change.'; redirect('pages/map_patients_users.php'); }

                try {
                    // Add column if not present
                    $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'patient_id'");
                    $colChk->execute(); $rr = $colChk->fetch(PDO::FETCH_ASSOC);
                    if (!empty($rr) && intval($rr['cnt']) > 0) {
                        $_SESSION['success'] = 'Column already exists.';
                        redirect('pages/map_patients_users.php');
                    }

                    $db->exec('ALTER TABLE users ADD COLUMN patient_id INT NULL AFTER doctor_id');
                    // Add index for quick lookup
                    try { $db->exec('ALTER TABLE users ADD INDEX idx_users_patient_id (patient_id)'); } catch (Exception $e) { /* ignore */ }
                    // Optionally add FK if patients table exists
                    try {
                        $tblChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients'");
                        $tblChk->execute(); $tr = $tblChk->fetch(PDO::FETCH_ASSOC);
                        if (!empty($tr) && intval($tr['cnt']) > 0) {
                            try {
                                $db->exec('ALTER TABLE users ADD CONSTRAINT fk_users_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE SET NULL ON UPDATE CASCADE');
                            } catch (Exception $e) { logAction('MAP_NOTICE', 'Could not add FK: ' . $e->getMessage()); }
                        }
                    } catch (Exception $e) { /* ignore */ }

                    $_SESSION['success'] = 'Schema updated: users.patient_id added.';
                    logAction('SCHEMA_CHANGE', 'Added users.patient_id column');
                } catch (Exception $e) {
                    logAction('SCHEMA_ERROR', $e->getMessage());
                    $_SESSION['error'] = 'Failed to update schema: ' . $e->getMessage();
                }
            }
            redirect('pages/map_patients_users.php');
            break;

        // Consolidated notification handlers moved below to avoid duplication.

        case 'import_users':
            // CSV import handler
            if ($_POST) {
                // Ensure users table has doctor_id column to avoid SQL errors
                try {
                    $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'doctor_id'");
                    $colChk->execute();
                    $rowChk = $colChk->fetch(PDO::FETCH_ASSOC);
                    if (empty($rowChk) || intval($rowChk['cnt']) === 0) {
                        // Attempt to add the column
                        try {
                            $db->exec("ALTER TABLE users ADD COLUMN doctor_id INT NULL AFTER role");
                            // If doctors table exists, try adding FK
                            $tblChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors'");
                            $tblChk->execute();
                            $t = $tblChk->fetch(PDO::FETCH_ASSOC);
                            if (!empty($t) && intval($t['cnt']) > 0) {
                                try {
                                    $db->exec("ALTER TABLE users ADD CONSTRAINT fk_users_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL ON UPDATE CASCADE");
                                } catch (Exception $e) {
                                    // ignore FK errors but log
                                    logAction('IMPORT_NOTICE', 'Could not add fk_users_doctor: ' . $e->getMessage());
                                }
                            }
                        } catch (Exception $e) {
                            logAction('IMPORT_NOTICE', 'Could not add doctor_id column: ' . $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    logAction('IMPORT_NOTICE', 'Information schema check failed: ' . $e->getMessage());
                }
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/users.php');
                }
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['error'] = 'CSV upload failed.';
                    redirect('pages/users.php');
                }

                $replace = isset($_POST['replace_existing']) ? true : false;
                $tmp = $_FILES['csv_file']['tmp_name'];
                $fh = fopen($tmp, 'r');
                if (!$fh) {
                    $_SESSION['error'] = 'Unable to open uploaded CSV file.';
                    redirect('pages/users.php');
                }

                $row = 0; $created = 0; $updated = 0; $skipped = 0;
                while (($data = fgetcsv($fh)) !== false) {
                    $row++;
                    // skip empty lines
                    if (count($data) < 1) continue;
                    // allow header row detection
                    if ($row == 1 && preg_match('/username/i', implode(',', $data))) {
                        continue;
                    }
                    // Expect columns: username,email,role,doctor_id
                    $username = isset($data[0]) ? sanitizeInput($data[0]) : null;
                    $email = isset($data[1]) ? sanitizeInput($data[1]) : null;
                    $role = isset($data[2]) ? sanitizeInput($data[2]) : 'Receptionist';
                    $doctor_id = isset($data[3]) && $data[3] !== '' ? sanitizeInput($data[3]) : null;

                    if (empty($username)) { $skipped++; continue; }

                    // Check existing
                    $q = $db->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
                    $q->bindParam(':username', $username);
                    $q->execute();
                    if ($q->rowCount() > 0) {
                        $existing = $q->fetch(PDO::FETCH_ASSOC);
                        if ($replace) {
                            $up = $db->prepare('UPDATE users SET email = :email, role = :role' . ($doctor_id ? ', doctor_id = :doctor_id' : '') . ' WHERE user_id = :id');
                            $up->bindParam(':email', $email);
                            $up->bindParam(':role', $role);
                            if ($doctor_id) $up->bindParam(':doctor_id', $doctor_id);
                            $up->bindParam(':id', $existing['user_id']);
                            $up->execute();
                            $updated++;
                            // create set-password token and send link
                            if (!empty($email)) {
                                $token = bin2hex(random_bytes(32));
                                $expires = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_LIFETIME_MINUTES . ' minutes'));
                                $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                                $ins->bindParam(':user_id', $existing['user_id']);
                                $ins->bindParam(':token', $token);
                                $ins->bindParam(':expires_at', $expires);
                                $ins->execute();
                                // fetch username
                                $uqq = $db->prepare('SELECT username FROM users WHERE user_id = :id');
                                $uqq->bindParam(':id', $existing['user_id']); $uqq->execute(); $urow = $uqq->fetch(PDO::FETCH_ASSOC);
                                sendPasswordResetLink($email, $urow['username'], $token);
                            }
                        } else {
                            $skipped++;
                        }
                    } else {
                        // create user with random password and send set-password link
                        $random = bin2hex(random_bytes(8));
                        $hash = password_hash($random, PASSWORD_DEFAULT);
                        $ins = $db->prepare('INSERT INTO users (username, password, email, role, created_at' . ($doctor_id ? ', doctor_id' : '') . ') VALUES (:username, :password, :email, :role, NOW()' . ($doctor_id ? ', :doctor_id' : '') . ')');
                        $ins->bindParam(':username', $username);
                        $ins->bindParam(':password', $hash);
                        $ins->bindParam(':email', $email);
                        $ins->bindParam(':role', $role);
                        if ($doctor_id) $ins->bindParam(':doctor_id', $doctor_id);
                        $ins->execute();
                        $newId = $db->lastInsertId();
                        $created++;
                        if (!empty($email)) {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_LIFETIME_MINUTES . ' minutes'));
                            $ins2 = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                            $ins2->bindParam(':user_id', $newId);
                            $ins2->bindParam(':token', $token);
                            $ins2->bindParam(':expires_at', $expires);
                            $ins2->execute();
                            sendPasswordResetLink($email, $username, $token);
                        }
                    }
                }
                fclose($fh);
                $_SESSION['success'] = "Import complete. Created: $created, Updated: $updated, Skipped: $skipped";
            }
            redirect('pages/users.php');
            break;

        case 'reset_password':
            if ($_POST && isset($_POST['user_id'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/users.php');
                }
                $user_id = sanitizeInput($_POST['user_id']);
                $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
                if (empty($new_password)) {
                    $_SESSION['error'] = 'Password cannot be empty.';
                    redirect('pages/users.php');
                }
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $u = $db->prepare("UPDATE users SET password = :pw WHERE user_id = :id");
                $u->bindParam(':pw', $hash);
                $u->bindParam(':id', $user_id);
                if ($u->execute()) {
                    // Fetch user email/username to notify
                    $q = $db->prepare('SELECT username, email FROM users WHERE user_id = :id');
                    $q->bindParam(':id', $user_id);
                    $q->execute();
                    $userRow = $q->fetch(PDO::FETCH_ASSOC);

                    logAction('PASSWORD_RESET', "User ID: $user_id password reset by " . ($_SESSION['username'] ?? 'System'));
                    $_SESSION['success'] = 'Password reset successfully.';
                    if ($userRow && !empty($userRow['email'])) {
                        sendPasswordResetEmail($userRow['email'], $userRow['username']);
                    }
                } else {
                    throw new Exception('Failed to reset password');
                }
            }
            redirect('pages/users.php');
            break;

        case 'request_password_reset':
            if ($_POST && isset($_POST['email'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/password_reset_request.php');
                }
                $email = sanitizeInput($_POST['email']);
                if (empty($email)) {
                    $_SESSION['error'] = 'Email is required.';
                    redirect('pages/password_reset_request.php');
                }

                // Lookup user
                $q = $db->prepare('SELECT user_id, username FROM users WHERE email = :email LIMIT 1');
                $q->bindParam(':email', $email);
                $q->execute();
                if ($q->rowCount() == 0) {
                    // For security, do not reveal if email exists
                    $_SESSION['success'] = 'If that email exists in our system, a reset link has been sent.';
                    redirect('pages/password_reset_request.php');
                }

                $user = $q->fetch(PDO::FETCH_ASSOC);
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_LIFETIME_MINUTES . ' minutes'));

                $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                $ins->bindParam(':user_id', $user['user_id']);
                $ins->bindParam(':token', $token);
                $ins->bindParam(':expires_at', $expires);
                if ($ins->execute()) {
                    logAction('PASSWORD_RESET_REQUEST', "Password reset requested for user_id: " . $user['user_id']);
                    // send email with token link
                    sendPasswordResetLink($email, $user['username'], $token);
                }

                // Generic response
                $_SESSION['success'] = 'If that email exists in our system, a reset link has been sent.';
            }
            redirect('pages/password_reset_request.php');
            break;

        case 'change_password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/my_profile.php');
                }
                $user_id = $_SESSION['user_id'] ?? null;
                if (!$user_id) { $_SESSION['error'] = 'Not authenticated.'; redirect('index.php'); }

                $current = trim($_POST['current_password'] ?? '');
                $new = trim($_POST['new_password'] ?? '');
                $confirm = trim($_POST['confirm_password'] ?? '');

                if (empty($current) || empty($new) || empty($confirm)) {
                    $_SESSION['error'] = 'Please fill all password fields.';
                    redirect('pages/my_profile.php');
                }
                if ($new !== $confirm) {
                    $_SESSION['error'] = 'New passwords do not match.';
                    redirect('pages/my_profile.php');
                }

                // Fetch stored password
                $q = $db->prepare('SELECT password FROM users WHERE user_id = :id LIMIT 1');
                $q->bindParam(':id', $user_id);
                $q->execute();
                if ($q->rowCount() == 0) { $_SESSION['error'] = 'User not found.'; redirect('pages/my_profile.php'); }
                $row = $q->fetch(PDO::FETCH_ASSOC);
                $stored = $row['password'] ?? '';

                $match = false;
                if (preg_match('/^\$2[aby]\$[0-9]{2}\$/', $stored)) {
                    if (password_verify($current, $stored)) $match = true;
                } else {
                    // legacy plaintext fallback
                    if ($current === $stored) $match = true;
                }

                if (!$match) {
                    $_SESSION['error'] = 'Current password is incorrect.';
                    redirect('pages/my_profile.php');
                }

                // update password with secure hash
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $u = $db->prepare('UPDATE users SET password = :pw WHERE user_id = :id');
                $u->bindParam(':pw', $hash);
                $u->bindParam(':id', $user_id);
                if ($u->execute()) {
                    // cleanup any outstanding reset tokens for this user
                    try {
                        $c = $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = :uid');
                        $c->bindParam(':uid', $user_id);
                        $c->execute();
                    } catch (Exception $e) {
                        logAction('TOKEN_CLEANUP_AFTER_PASS_CHANGE_ERROR', $e->getMessage());
                    }
                    logAction('PASSWORD_CHANGE', 'User ID ' . $user_id . ' changed password');
                    $_SESSION['success'] = 'Password changed successfully.';
                    redirect('pages/my_profile.php');
                } else {
                    $_SESSION['error'] = 'Failed to update password.';
                    redirect('pages/my_profile.php');
                }
            }
            redirect('pages/my_profile.php');
            break;

        case 'login':
            if ($_POST && isset($_POST['username'])) {
                $username = sanitizeInput($_POST['username']);
                $password = isset($_POST['password']) ? trim($_POST['password']) : '';

                if (empty($username) || empty($password)) {
                    $_SESSION['error'] = 'Username and password are required.';
                    redirect('pages/login.php');
                }

                try {
                    // Use distinct placeholders for username and email to avoid PDO "Invalid parameter number"
                    // Use LOWER() for case-insensitive lookup (important for SQLite which is CS by default for =)
                    $q = $db->prepare('SELECT user_id, username, password, role, doctor_id FROM users WHERE LOWER(username) = LOWER(:u) OR LOWER(email) = LOWER(:e) LIMIT 1');
                    $q->bindParam(':u', $username);
                    $q->bindParam(':e', $username);
                    $q->execute();
                    $row = $q->fetch(PDO::FETCH_ASSOC);

                    if (!$row) {
                        logAction('LOGIN_FAILED', "User not found: $username");
                        $_SESSION['error'] = 'Invalid username or password.';
                        redirect('pages/login.php');
                    }

                    $stored = $row['password'] ?? '';
                    $ok = false;

                    // If stored password looks like a bcrypt hash, use password_verify
                    if (preg_match('/^\$2[aby]\$[0-9]{2}\$/', $stored)) {
                        if (password_verify($password, $stored)) $ok = true;
                    } else {
                        // Legacy/plaintext fallback: compare directly and re-hash on success
                        if ($password === $stored) {
                            $ok = true;
                            try {
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                $u = $db->prepare('UPDATE users SET password = :pw WHERE user_id = :id');
                                $u->bindValue(':pw', $newHash);
                                $u->bindValue(':id', $row['user_id']);
                                $u->execute();
                                logAction('PASSWORD_UPGRADE', 'Upgraded plaintext password to hash for user ' . $row['username']);
                            } catch (Exception $e) {
                                logAction('PASSWORD_UPGRADE_FAIL', $e->getMessage());
                            }
                        }
                    }

                    if (!$ok) {
                        // Additional debug info: record hash type and verification result to process.log
                        $hashType = preg_match('/^\$2[aby]\$[0-9]{2}\$/', $stored) ? 'bcrypt' : (strpos($stored, '$') === 0 ? 'unknown_hash' : 'plaintext');
                        $pv = false;
                        try { $pv = password_verify($password, $stored); } catch (Exception $e) { $pv = false; }
                        $dbg = "Invalid password for user: $username | hash_type: $hashType | password_verify: " . ($pv ? 'true' : 'false');
                        logAction('LOGIN_FAILED', $dbg);
                        $_SESSION['error'] = 'Invalid username or password.';
                        redirect('pages/login.php');
                    }

                    // Successful login
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = strtolower($row['role']);
                    $_SESSION['doctor_id'] = $row['doctor_id'] ?? null;
                    
                    // Update last_login
                    try {
                        $up_login = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :uid");
                        $up_login->execute(['uid' => $row['user_id']]);

                        // Log session start
                        $ins_session = $db->prepare("INSERT INTO user_logins (user_id, login_time, ip_address, user_agent, status) VALUES (:uid, CURRENT_TIMESTAMP, :ip, :ua, 'active')");
                        $ins_session->execute([
                            'uid' => $row['user_id'],
                            'ip'  => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                            'ua'  => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
                        ]);
                        $_SESSION['login_log_id'] = $db->lastInsertId();
                    } catch (Exception $e) { /* ignore */ }

                    logAuth('LOGIN', $_SESSION['username'], $_SESSION['user_id']);
                    
                    // Notify Admins and Receptionists
                    $loginMsg = "User " . $_SESSION['username'] . " (" . $_SESSION['role'] . ") has logged into the system.";
                    notifyRoles($db, ['admin', 'receptionist'], 'auth', 'Staff Login', $loginMsg, ['user_id' => $_SESSION['user_id']]);
                    logAction('USER_LOGIN', 'User logged in: ' . $_SESSION['username']);

                    // Redirect to dashboard or based on role
                    if (in_array($_SESSION['role'], ['admin','receptionist','doctor'])) {
                        redirect('pages/dashboard.php');
                    } else {
                        redirect('pages/waiting_status.php');
                    }

                } catch (PDOException $e) {
                    logAction('LOGIN_ERROR', 'Database error during login: ' . $e->getMessage());
                    $_SESSION['error'] = 'Database error occurred. Please try again.';
                    redirect('pages/login.php');
                }
            }
            redirect('pages/login.php');
            break;

        case 'perform_password_reset':
            if ($_POST && isset($_POST['token'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/password_reset_request.php');
                }
                $token = sanitizeInput($_POST['token']);
                $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
                $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

                if (empty($new_password) || empty($confirm_password)) {
                    $_SESSION['error'] = 'Please provide and confirm your new password.';
                    redirect('pages/password_reset.php?token=' . urlencode($token));
                }
                if ($new_password !== $confirm_password) {
                    $_SESSION['error'] = 'Passwords do not match.';
                    redirect('pages/password_reset.php?token=' . urlencode($token));
                }

                // Remove any expired tokens (cleanup) and validate the provided token as unused and not expired
                try {
                    $cleanup = $db->prepare('DELETE FROM password_reset_tokens WHERE expires_at < NOW()');
                    $cleanup->execute();
                } catch (Exception $e) {
                    // Non-fatal: log cleanup failure but continue to validation
                    logAction('TOKEN_CLEANUP_ERROR', $e->getMessage());
                }

                $t = $db->prepare('SELECT * FROM password_reset_tokens WHERE token = :token AND (used_at IS NULL OR used_at = "") AND expires_at >= NOW() LIMIT 1');
                $t->bindParam(':token', $token);
                $t->execute();
                if ($t->rowCount() == 0) {
                    $_SESSION['error'] = 'Invalid or expired token.';
                    redirect('pages/password_reset_request.php');
                }
                $row = $t->fetch(PDO::FETCH_ASSOC);

                // Update user password
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $u = $db->prepare('UPDATE users SET password = :pw WHERE user_id = :id');
                $u->bindParam(':pw', $hash);
                $u->bindParam(':id', $row['user_id']);
                if ($u->execute()) {
                    // Remove the token immediately after successful reset to prevent reuse
                    $m = $db->prepare('DELETE FROM password_reset_tokens WHERE id = :id');
                    $m->bindParam(':id', $row['id']);
                    $m->execute();

                    // Notify user by email
                    $q2 = $db->prepare('SELECT email, username FROM users WHERE user_id = :id');
                    $q2->bindParam(':id', $row['user_id']);
                    $q2->execute();
                    $usr = $q2->fetch(PDO::FETCH_ASSOC);
                    if ($usr && !empty($usr['email'])) sendPasswordResetEmail($usr['email'], $usr['username']);

                    logAction('PASSWORD_RESET_COMPLETE', 'Password reset completed for user_id: ' . $row['user_id']);
                    $_SESSION['success'] = 'Password updated successfully. You may now login.';
                    redirect('index.php');
                } else {
                    throw new Exception('Failed to update password.');
                }
            }
            redirect('pages/password_reset_request.php');
            break;

        case 'send_appointment_mail':
            // Send appointment notification email(s) for a given appointment
            if ((
                ($_POST && isset($_POST['appointment_id'])) || isset($_GET['id'])
            )) {
                // Accept POST (preferred) or GET id param
                $appointment_id = isset($_POST['appointment_id']) ? sanitizeInput($_POST['appointment_id']) : sanitizeInput($_GET['id']);

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/print_appointment.php?id=' . urlencode($appointment_id));
                }

                try {
                    $q = $db->prepare('SELECT a.*, p.first_name AS pfn, p.last_name AS pln, p.email AS pemail, d.first_name AS dfn, d.last_name AS dln, d.email AS demail FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id LEFT JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.appointment_id = :id LIMIT 1');
                    $q->bindParam(':id', $appointment_id);
                    $q->execute();
                    if ($q->rowCount() == 0) {
                        $_SESSION['error'] = 'Appointment not found.';
                        redirect('pages/appointments.php');
                    }
                    $row = $q->fetch(PDO::FETCH_ASSOC);
                    $patientEmail = $row['pemail'] ?? '';
                    $doctorEmail = $row['demail'] ?? '';
                    $patientName = trim(($row['pfn'] ?? '') . ' ' . ($row['pln'] ?? ''));
                    $doctorName = trim(($row['dfn'] ?? '') . ' ' . ($row['dln'] ?? ''));

                    $resultDetails = [];
                    $sentToPatient = ['ok' => false, 'error' => null];
                    $sentToDoctor = ['ok' => false, 'error' => null];
                    if (!empty($patientEmail)) {
                        $sentToPatient = sendAppointmentNotificationToPatient($patientEmail, $patientName, $doctorName, $row['appointment_date'], $row['appointment_time'], $row['notes'] ?? '');
                        $resultDetails['patient'] = $sentToPatient;
                    }
                    if (!empty($doctorEmail)) {
                        $sentToDoctor = sendAppointmentNotificationToDoctor($doctorEmail, $doctorName, $patientName, $row['appointment_date'], $row['appointment_time'], $row['notes'] ?? '');
                        $resultDetails['doctor'] = $sentToDoctor;
                    }

                    $msgs = [];
                    if (!empty($resultDetails['patient']) && $resultDetails['patient']['ok']) $msgs[] = 'Sent to patient'; else $msgs[] = 'Patient email not sent';
                    if (!empty($resultDetails['doctor']) && $resultDetails['doctor']['ok']) $msgs[] = 'Sent to doctor'; else $msgs[] = 'Doctor email not sent';
                    $msgText = 'Mail action completed: ' . implode('; ', $msgs);
                    // If AJAX request, return JSON instead of redirecting.
                    // Accept either X-Requested-With, an Accept: application/json header, or explicit ajax=1 param.
                    $isAjax = (
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                    );
                    // Insert notifications for current user (if available) with details
                    try {
                        $actor = $_SESSION['user_id'] ?? null;
                        if ($actor) {
                            $detailMsg = $msgText;
                            $meta = ['appointment_id' => $appointment_id, 'results' => $resultDetails];
                            createNotification($db, $actor, 'appointment_mail', 'Appointment Mail', $detailMsg, $meta);
                        }
                    } catch (Exception $e) { logAction('NOTIF_INSERT_ERROR', $e->getMessage()); }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'message' => $msgText, 'details' => $resultDetails, 'toast' => true]);
                        exit;
                    }
                    $_SESSION['success'] = $msgText;
                    redirect('pages/print_appointment.php?id=' . $appointment_id);
                } catch (Exception $e) {
                    logAction('SEND_APPOINTMENT_MAIL_ERROR', $e->getMessage());
                    $isAjax = (
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
                    );
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => false, 'message' => 'Failed to send appointment email.']);
                        exit;
                    }
                    $_SESSION['error'] = 'Failed to send appointment email.';
                    redirect('pages/print_appointment.php?id=' . $appointment_id);
                }
            }
            redirect('pages/appointments.php');
            break;

        case 'save_theme':
            // Lightweight handler to persist user's theme preference (session + DB if available)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
                $theme = sanitizeInput($_POST['theme']);
                // Accept only 'light' or 'dark'
                if (!in_array($theme, ['light','dark'])) $theme = 'light';
                // Save to session so it's immediate
                $_SESSION['theme'] = $theme;

                // Attempt to persist to users table if logged in and column exists
                if (!empty($_SESSION['user_id'])) {
                    try {
                        $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME IN ('theme','theme_preference')");
                        $colChk->execute(); $rowChk = $colChk->fetch(PDO::FETCH_ASSOC);
                        if (!empty($rowChk) && intval($rowChk['cnt']) > 0) {
                            // prefer column 'theme' then 'theme_preference'
                            $colUsed = null;
                            $c1 = $db->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME IN ('theme','theme_preference') ORDER BY FIELD(COLUMN_NAME,'theme','theme_preference') LIMIT 1");
                            $c1->execute(); $cRow = $c1->fetch(PDO::FETCH_ASSOC);
                            if (!empty($cRow['COLUMN_NAME'])) $colUsed = $cRow['COLUMN_NAME'];
                            if ($colUsed) {
                                $upd = $db->prepare("UPDATE users SET $colUsed = :val WHERE user_id = :id");
                                $upd->bindParam(':val', $theme);
                                $upd->bindParam(':id', $_SESSION['user_id']);
                                $upd->execute();
                            }
                        }
                    } catch (Exception $e) {
                        // non-fatal: log and continue
                        logAction('SAVE_THEME_ERROR', $e->getMessage());
                    }
                }

                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'theme' => $_SESSION['theme']]);
                    exit;
                }
            }
            // fallback redirect
            redirect('pages/my_profile.php');
            break;

        case 'log_print':
            // Log a print attempt for an appointment and return JSON
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $appointment_id = isset($_POST['appointment_id']) ? sanitizeInput($_POST['appointment_id']) : (isset($_GET['appointment_id']) ? sanitizeInput($_GET['appointment_id']) : null);
                try {
                    if (empty($appointment_id)) {
                        throw new Exception('Missing appointment id');
                    }
                    $q = $db->prepare('SELECT appointment_id FROM appointments WHERE appointment_id = :id LIMIT 1');
                    $q->bindParam(':id', $appointment_id);
                    $q->execute();
                    if ($q->rowCount() == 0) throw new Exception('Appointment not found');

                    // Log the print event
                    logAction('PRINT_APPOINTMENT', 'Appointment printed: ' . $appointment_id . ' by ' . ($_SESSION['username'] ?? 'guest'));

                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'message' => 'Print logged']);
                        exit;
                    }
                    $_SESSION['success'] = 'Print logged.';
                } catch (Exception $e) {
                    logAction('PRINT_LOG_ERROR', $e->getMessage());
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
                        exit;
                    }
                    $_SESSION['error'] = 'Failed to log print: ' . $e->getMessage();
                }
            }
            redirect('pages/appointments.php');
            break;

        case 'notifications_unread_count':
            $user_id = $_SESSION['user_id'] ?? null;
            header('Content-Type: application/json');
            if (!$user_id) { echo json_encode(['ok'=>false,'count'=>0]); exit; }
            try {
                $q = $db->prepare('SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :uid AND is_read = 0');
                $q->execute(['uid' => $user_id]);
                $r = $q->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['ok'=>true,'count'=>intval($r['cnt'] ?? 0)]);
            } catch (Exception $e) { echo json_encode(['ok'=>false,'count'=>0]); }
            exit;

        case 'notifications_fetch':
            $user_id = $_SESSION['user_id'] ?? null;
            header('Content-Type: application/json');
            if (!$user_id) { echo json_encode(['ok'=>false,'notifications'=>[]]); exit; }
            try {
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $q = $db->prepare('SELECT id, type, title, message, meta, is_read, created_at FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim');
                $q->bindValue(':uid', $user_id);
                $q->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
                $q->execute();
                $notifs = $q->fetchAll(PDO::FETCH_ASSOC);
                
                // Absolute max ID for polling initialization
                $q2 = $db->prepare("SELECT MAX(id) as max_id FROM notifications WHERE user_id = ?");
                $q2->execute([$user_id]);
                $maxRow = $q2->fetch(PDO::FETCH_ASSOC);
                $max_id = intval($maxRow['max_id'] ?? 0);

                echo json_encode(['ok'=>true, 'notifications'=>$notifs, 'max_id' => $max_id]);
            } catch (Exception $e) { echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]); }
            exit;

        case 'notifications_poll':
            $user_id = $_SESSION['user_id'] ?? null;
            header('Content-Type: application/json');
            if (!$user_id) { echo json_encode(['ok'=>false,'notifications'=>[]]); exit; }
            try {
                $after_id = isset($_GET['after_id']) ? intval($_GET['after_id']) : 0;
                $q = $db->prepare("SELECT id, type, title, message, meta, is_read, created_at FROM notifications WHERE user_id = :uid AND id > :after AND is_read = 0 ORDER BY id ASC");
                $q->execute(['uid'=>$user_id, 'after'=>$after_id]);
                $new = $q->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['ok'=>true, 'notifications'=>$new]);
            } catch (Exception $e) { echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]); }
            exit;

        case 'notifications_mark_read':
            $user_id = $_SESSION['user_id'] ?? null;
            header('Content-Type: application/json');
            if (!$user_id) { echo json_encode(['ok'=>false]); exit; }
            $ids = $_POST['ids'] ?? [];
            if (empty($ids)) { echo json_encode(['ok'=>false,'message'=>'No ids']); exit; }
            try {
                $ids_clean = is_array($ids) ? array_map('intval', $ids) : [intval($ids)];
                $placeholders = implode(',', array_fill(0, count($ids_clean), '?'));
                $q = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders) AND user_id = ?");
                $params = array_merge($ids_clean, [$user_id]);
                $q->execute($params);
                echo json_encode(['ok'=>true]);
            } catch (Exception $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
            exit;

        case 'save_prescription':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) {
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Invalid CSRF token']); exit; }
                    $_SESSION['error'] = 'Invalid CSRF token.'; redirect('pages/dashboard.php');
                }
                $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
                $html = isset($_POST['prescription_html']) ? $_POST['prescription_html'] : '';
                // Basic sanitization: remove script tags and on* attributes (not a full sanitizer). Recommend HTMLPurifier for production.
                $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
                $html = preg_replace_callback('#<([a-zA-Z0-9]+)([^>]*)>#', function($m){
                    $tag = $m[1]; $attrs = $m[2];
                    // remove event handlers like onclick, onload, etc.
                    $attrs = preg_replace("/\\son[a-z]+=(['\"]).*?\\1/is", '', $attrs);
                    return '<'.$tag.$attrs.'>';
                }, $html);

                try {
                    // Attempt DB-backed storage if $db is available
                    if (isset($db) && $db instanceof PDO) {
                        // Find appointment/doctor/patient info for metadata
                        $q = $db->prepare('SELECT doctor_id, patient_id FROM appointments WHERE appointment_id = :id LIMIT 1');
                        $q->bindParam(':id', $appointment_id);
                        $q->execute();
                        $row = $q->fetch(PDO::FETCH_ASSOC);
                        $doctor_id = $row['doctor_id'] ?? null;
                        $patient_id = $row['patient_id'] ?? null;

                        // Insert or update: if a prescription exists for this appointment, update latest; otherwise insert new
                        $existing = $db->prepare('SELECT id FROM prescriptions WHERE appointment_id = :aid ORDER BY created_at DESC LIMIT 1');
                        $existing->bindParam(':aid', $appointment_id);
                        $existing->execute();
                        if ($existing->rowCount()) {
                            $eid = $existing->fetchColumn();
                            $up = $db->prepare('UPDATE prescriptions SET content = :c, updated_at = NOW() WHERE id = :id');
                            $up->bindParam(':c', $html);
                            $up->bindParam(':id', $eid);
                            $up->execute();
                            $presc_id = $eid;
                        } else {
                            $ins = $db->prepare('INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, content, created_by) VALUES (:aid, :did, :pid, :c, :cb)');
                            $created_by = $_SESSION['user_id'] ?? null;
                            $ins->bindParam(':aid', $appointment_id);
                            $ins->bindParam(':did', $doctor_id);
                            $ins->bindParam(':pid', $patient_id);
                            $ins->bindParam(':c', $html);
                            $ins->bindParam(':cb', $created_by);
                            $ins->execute();
                            $presc_id = $db->lastInsertId();
                        }
                        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'message'=>'Prescription saved','prescription_id'=>$presc_id]); exit; }
                        $_SESSION['success'] = 'Prescription saved.'; redirect('pages/prescription_edit.php?appointment_id=' . $appointment_id);
                    } else {
                        // Fallback to file storage as before
                        $dir = __DIR__ . '/prescriptions';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $ts = date('Ymd_His');
                        $fname = "prescription_{$appointment_id}_{$ts}.html";
                        $path = $dir . '/' . $fname;
                        file_put_contents($path, $html);
                        $meta = ['appointment_id'=>$appointment_id, 'created_at'=>date('c'), 'file'=>$fname, 'author_id'=> ($_SESSION['user_id'] ?? null)];
                        file_put_contents($dir . "/{$fname}.meta.json", json_encode($meta));
                        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'message'=>'Prescription saved']); exit; }
                        $_SESSION['success'] = 'Prescription saved.'; redirect('pages/prescription_edit.php?appointment_id=' . $appointment_id);
                    }
                } catch (Exception $e) {
                    logAction('SAVE_PRESCRIPTION_ERROR', $e->getMessage());
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Failed to save prescription']); exit; }
                    $_SESSION['error'] = 'Failed to save prescription.'; redirect('pages/prescription_edit.php?appointment_id=' . $appointment_id);
                }
            }
            redirect('pages/dashboard.php');
            break;

        case 'send_prescription_mail':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
                if (!verify_csrf()) {
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Invalid CSRF token']); exit; }
                    $_SESSION['error'] = 'Invalid CSRF token.'; redirect('pages/dashboard.php');
                }
                $appointment_id = intval($_POST['appointment_id']);
                try {
                    $q = $db->prepare('SELECT a.*, p.first_name AS pfn, p.last_name AS pln, p.email AS pemail, d.first_name AS dfn, d.last_name AS dln FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id LEFT JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.appointment_id = :id LIMIT 1');
                    $q->bindParam(':id', $appointment_id);
                    $q->execute();
                    if ($q->rowCount()==0) throw new Exception('Appointment not found');
                    $row = $q->fetch(PDO::FETCH_ASSOC);
                    $patientEmail = $row['pemail'] ?? '';
                    $patientName = trim(($row['pfn'] ?? '') . ' ' . ($row['pln'] ?? ''));
                    // find latest prescription
                    $dir = __DIR__ . '/prescriptions';
                    $html = '';
                    if (is_dir($dir)) {
                        $files = glob($dir . "/prescription_{$appointment_id}_*.html");
                        if ($files) {
                            usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
                            $html = file_get_contents($files[0]);
                        }
                    }
                    if (empty($html)) throw new Exception('No prescription file found');
                    $subject = SITE_NAME . ' - Prescription';
                    $body = '<p>Dear ' . htmlspecialchars($patientName) . ',</p>' . $html . '<p>Regards,<br>' . SITE_NAME . '</p>';
                    $sent = false;
                    if (!empty($patientEmail)) {
                        $sent = sendMail($patientEmail, $subject, $body);
                    }
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        if ($sent) echo json_encode(['ok'=>true,'message'=>'Prescription sent to patient']); else echo json_encode(['ok'=>false,'message'=>'Failed to send']);
                        exit;
                    }
                    if ($sent) { $_SESSION['success'] = 'Prescription sent to patient.'; } else { $_SESSION['error'] = 'Failed to send prescription.'; }
                    redirect('pages/prescription_edit.php?appointment_id=' . $appointment_id);
                } catch (Exception $e) {
                    logAction('SEND_PRESCRIPTION_ERROR', $e->getMessage());
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Failed to send prescription']); exit; }
                    $_SESSION['error'] = 'Failed to send prescription.'; redirect('pages/prescription_edit.php?appointment_id=' . $appointment_id);
                }
            }
            redirect('pages/dashboard.php');
            break;

        case 'update_appointment_status':
            header('Content-Type: application/json');
            if (!isLoggedIn()) { echo json_encode(['ok'=>false,'message'=>'Unauthorized']); exit; }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid method']); exit; }
            if (!verify_csrf()) { echo json_encode(['ok'=>false,'message'=>'Invalid CSRF token']); exit; }
            $id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';
            $allowed = ['scheduled','arrived','visiting','completed','cancelled','no-show'];
            if (!$id || !in_array($status, $allowed)) {
                echo json_encode(['ok'=>false,'message'=>'Invalid parameters']); exit;
            }
            try {
                $up = $db->prepare('UPDATE appointments SET status = :s, updated_at = NOW() WHERE appointment_id = :id');
                $up->execute(['s'=>$status, 'id'=>$id]);

                // Notify Admins and Receptionists
                $infoQ = $db->prepare("SELECT p.first_name, p.last_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_id = :id");
                $infoQ->execute(['id'=>$id]);
                $pat = $infoQ->fetch(PDO::FETCH_ASSOC);
                if ($pat) {
                    $msg = "Appointment for " . $pat['first_name'] . " " . $pat['last_name'] . " updated to " . ucfirst($status) . ".";
                    notifyRoles($db, ['admin', 'receptionist'], 'status', 'Status Update', $msg, ['appointment_id' => $id, 'status' => $status]);
                }
                logAction('STATUS_UPDATE', "Appointment $id status set to $status");
                echo json_encode(['ok'=>true, 'message'=>'Status updated to ' . ucfirst($status)]);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
            }
            exit;
            break;

        case 'save_consultation':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) { $_SESSION['error'] = 'Invalid CSRF token.'; redirect('pages/dashboard.php'); }
                
                $patient_id = intval($_POST['patient_id'] ?? 0);
                $appointment_id = intval($_POST['appointment_id'] ?? 0);
                $main_symptom = sanitizeInput($_POST['main_symptom'] ?? '');
                $additional = sanitizeInput($_POST['additional_symptoms'] ?? '');
                $severity = sanitizeInput($_POST['severity'] ?? 'Low');
                $specialty = sanitizeInput($_POST['recommended_specialty'] ?? '');
                $confidence = floatval($_POST['ai_confidence'] ?? 0.0);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                $bp = sanitizeInput($_POST['bp'] ?? '');
                $pulse = sanitizeInput($_POST['pulse'] ?? '');
                $weight = sanitizeInput($_POST['weight'] ?? '');
                $temp = sanitizeInput($_POST['temperature'] ?? '');
                $spo2 = sanitizeInput($_POST['spo2'] ?? '');
                $diagnosis = sanitizeInput($_POST['diagnosis'] ?? '');
                $plan = sanitizeInput($_POST['treatment_plan'] ?? '');

                // Get current doctor id
                $doctor_id = null;
                if (strtolower($_SESSION['role'] ?? '') === 'doctor') {
                    $q = $db->prepare("SELECT doctor_id FROM users WHERE user_id = :uid");
                    $q->execute(['uid' => $_SESSION['user_id']]);
                    $doctor_id = $q->fetchColumn() ?: null;
                }

                try {
                    // Check if record exists for this appointment
                    $check = $db->prepare("SELECT id FROM consultation_history WHERE appointment_id = ?");
                    $check->execute([$appointment_id]);
                    $existing_id = $check->fetchColumn();

                    if ($existing_id) {
                        $sql = "UPDATE consultation_history SET 
                                main_symptom = :ms, additional_symptoms = :as, severity = :sev, 
                                recommended_specialty = :spec, ai_confidence = :conf, notes = :notes,
                                bp = :bp, pulse = :pulse, weight = :weight, temperature = :temp, spo2 = :spo2,
                                diagnosis = :diag, treatment_plan = :plan
                                WHERE id = :id";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            'ms'=>$main_symptom, 'as'=>$additional, 'sev'=>$severity, 'spec'=>$specialty,
                            'conf'=>$confidence, 'notes'=>$notes, 'bp'=>$bp, 'pulse'=>$pulse,
                            'weight'=>$weight, 'temp'=>$temp, 'spo2'=>$spo2, 'diag'=>$diagnosis,
                            'plan'=>$plan, 'id'=>$existing_id
                        ]);
                    } else {
                        $sql = "INSERT INTO consultation_history (patient_id, doctor_id, appointment_id, main_symptom, additional_symptoms, severity, recommended_specialty, ai_confidence, notes, bp, pulse, weight, temperature, spo2, diagnosis, treatment_plan) 
                                VALUES (:pid, :did, :aid, :ms, :as, :sev, :spec, :conf, :notes, :bp, :pulse, :weight, :temp, :spo2, :diag, :plan)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            'pid'=>$patient_id, 'did'=>$doctor_id, 'aid'=>$appointment_id, 'ms'=>$main_symptom,
                            'as'=>$additional, 'sev'=>$severity, 'spec'=>$specialty, 'conf'=>$confidence,
                            'notes'=>$notes, 'bp'=>$bp, 'pulse'=>$pulse, 'weight'=>$weight,
                            'temp'=>$temp, 'spo2'=>$spo2, 'diag'=>$diagnosis, 'plan'=>$plan
                        ]);
                    }

                    // Update appointment status to completed if requested
                    if (isset($_POST['complete_appointment']) && $_POST['complete_appointment'] == '1') {
                        $db->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE appointment_id = ?")->execute([$appointment_id]);
                        notifyRoles($db, ['admin', 'receptionist'], 'status', 'Consultation Completed', "Consultation for appointment #$appointment_id has been completed.");
                    }

                    $_SESSION['success'] = 'Consultation record saved successfully.';
                    $target = ($_POST['redirect'] ?? '') === 'dashboard' ? 'pages/dashboard.php' : 'pages/consultation_history.php?patient_id=' . $patient_id;
                    redirect($target);
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Failed to save: ' . $e->getMessage();
                    redirect('pages/dashboard.php');
                }
            }
            break;

        case 'save_announcement':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) { $_SESSION['error'] = 'Invalid CSRF token.'; redirect('pages/dashboard.php'); }
                if (!in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'root'])) { redirect('pages/dashboard.php'); }

                $title = sanitizeInput($_POST['title'] ?? '');
                $msg = sanitizeInput($_POST['message'] ?? '');
                $target = sanitizeInput($_POST['target_role'] ?? 'all');
                $uid = $_SESSION['user_id'];

                try {
                    $ins = $db->prepare("INSERT INTO announcements (title, message, target_role, created_by) VALUES (:t, :m, :tr, :cb)");
                    $ins->execute(['t'=>$title, 'm'=>$msg, 'tr'=>$target, 'cb'=>$uid]);
                    
                    // Broadcast notification
                    $notifMsg = "New Announcement: " . $title;
                    if ($target === 'all') {
                        notifyRoles($db, ['admin', 'receptionist', 'doctor'], 'announcement', 'System Announcement', $msg);
                    } else {
                        notifyRole($db, $target, 'announcement', 'System Announcement', $msg);
                    }

                    $_SESSION['success'] = 'Announcement posted and notifications sent.';
                    redirect('pages/manage_announcements.php');
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Failed: ' . $e->getMessage();
                    redirect('pages/manage_announcements.php');
                }
            }
            break;

        case 'delete_announcement':
            if (!in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'root'])) { redirect('pages/dashboard.php'); }
            $id = intval($_GET['id'] ?? 0);
            if ($id) {
                try {
                    $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
                    $_SESSION['success'] = 'Announcement deleted.';
                } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
            }
            redirect('pages/manage_announcements.php');
            break;

        case 'call_next_patient':
            header('Content-Type: application/json');
            if (!isLoggedIn()) { echo json_encode(['ok'=>false,'message'=>'Unauthorized']); exit; }
            if (!verify_csrf()) { echo json_encode(['ok'=>false,'message'=>'Invalid CSRF token']); exit; }
            $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
            if (!$doctor_id) { echo json_encode(['ok'=>false,'message'=>'Doctor ID required']); exit; }
            try {
                $q = $db->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = :did AND appointment_date = CURDATE() AND status IN ('scheduled', 'arrived') ORDER BY appointment_serial ASC, appointment_time ASC LIMIT 1");
                $q->execute(['did'=>$doctor_id]);
                $id = $q->fetchColumn();
                if (!$id) {
                    echo json_encode(['ok'=>false, 'message'=>'No more patients in queue for today.']);
                    exit;
                }
                $up = $db->prepare("UPDATE appointments SET status = 'visiting', updated_at = NOW() WHERE appointment_id = :id");
                $up->execute(['id'=>$id]);
                
                // Fetch info for notification
                $infoQ = $db->prepare("SELECT a.appointment_serial, p.first_name as pfn, p.last_name as pln, d.first_name as dfn, d.last_name as dln FROM appointments a JOIN patients p ON a.patient_id = p.patient_id JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.appointment_id = :id");
                $infoQ->execute(['id'=>$id]);
                $info = $infoQ->fetch(PDO::FETCH_ASSOC);
                
                if ($info) {
                    $docName = "Dr. " . $info['dfn'] . " " . $info['dln'];
                    $patName = $info['pfn'] . " " . $info['pln'];
                    $notifMsg = "$docName is now visiting $patName (Serial #" . $info['appointment_serial'] . ")";
                    notifyRoles($db, ['admin', 'receptionist'], 'queue', 'Next Patient Called', $notifMsg, ['appointment_id' => $id, 'doctor_id' => $doctor_id]);
                }

                logAction('CALL_NEXT', "Doctor $doctor_id called appointment $id");
                echo json_encode(['ok'=>true, 'appointment_id'=>$id, 'message'=>'Calling next patient...']);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
            }
            exit;
            break;

        case 'save_vitals':
            header('Content-Type: application/json');
            if (!isLoggedIn()) { echo json_encode(['ok'=>false,'message'=>'Unauthorized']); exit; }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid method']); exit; }
            if (!verify_csrf()) { echo json_encode(['ok'=>false,'message'=>'Invalid CSRF token']); exit; }
            
            $id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
            $bp = sanitizeInput($_POST['bp'] ?? '');
            $pulse = sanitizeInput($_POST['pulse'] ?? '');
            $weight = sanitizeInput($_POST['weight'] ?? '');
            $temp = sanitizeInput($_POST['temperature'] ?? '');
            $spo2 = sanitizeInput($_POST['spo2'] ?? '');
            
            if (!$id) { echo json_encode(['ok'=>false,'message'=>'Appointment ID required']); exit; }
            
            try {
                $up = $db->prepare('UPDATE appointments SET bp = :bp, pulse = :p, weight = :w, temperature = :t, spo2 = :s, updated_at = NOW() WHERE appointment_id = :id');
                $up->execute(['bp'=>$bp, 'p'=>$pulse, 'w'=>$weight, 't'=>$temp, 's'=>$spo2, 'id'=>$id]);
                logAction('VITALS_SAVED', "Vitals saved for appointment $id");
                
                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    echo json_encode(['ok'=>true, 'message'=>'Vitals saved successfully']);
                } else {
                    $_SESSION['success'] = 'Vitals saved successfully.';
                    redirect('pages/dashboard.php');
                }
            } catch (Exception $e) {
                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
                } else {
                    $_SESSION['error'] = 'Error: ' . $e->getMessage();
                    redirect('pages/dashboard.php');
                }
            }
            exit;
            break;

        case 'search_medicine':
            header('Content-Type: application/json');
            $q = trim($_GET['q'] ?? '');
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            if (strlen($q) < 2) { echo json_encode([]); exit; }
            
            try {
                // Optimized single-pass query with manufacturer prioritization
                $sql = "SELECT MIN(id) as id, brand_name, generic_name, dosage_form, strength, manufacturer, drug_class, indication, unit_price 
                        FROM medicine_master_data 
                        WHERE brand_name LIKE :q1 OR generic_name LIKE :q2 OR indication LIKE :q3
                        GROUP BY brand_name, generic_name, strength, dosage_form
                        ORDER BY 
                            (CASE 
                                WHEN manufacturer LIKE '%Square%' THEN 1
                                WHEN manufacturer LIKE '%Beximco%' THEN 2
                                WHEN manufacturer LIKE '%Incepta%' THEN 3
                                WHEN manufacturer LIKE '%ACME%' THEN 4
                                WHEN manufacturer LIKE '%Aristopharma%' THEN 5
                                WHEN manufacturer LIKE '%Ibn Sina%' THEN 6
                                ELSE 10 
                            END) ASC,
                            (brand_name LIKE :eq1) DESC,
                            (generic_name LIKE :eq2) DESC,
                            brand_name ASC
                        LIMIT :limit OFFSET :offset";
                
                $stmt = $db->prepare($sql);
                $search = "%$q%";
                $stmt->bindValue(':q1', $search, PDO::PARAM_STR);
                $stmt->bindValue(':q2', $search, PDO::PARAM_STR);
                $stmt->bindValue(':q3', $search, PDO::PARAM_STR);
                $stmt->bindValue(':eq1', $q, PDO::PARAM_STR);
                $stmt->bindValue(':eq2', $q, PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add a 'type' field to maintain compatibility with existing JS if needed, 
                // although we are simplifying.
                foreach ($results as &$r) {
                    $r['type'] = 'primary'; 
                }

                echo json_encode($results);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
            }
            exit;
            break;

        case 'get_medicine_count':
            header('Content-Type: application/json');
            try {
                $stmt = $db->query("SELECT COUNT(*) as total FROM medicine_master_data");
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['ok'=>true, 'total'=>$res['total']]);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
            }
            exit;
            break;

        case 'update_medicine_price':
            header('Content-Type: application/json');
            // Public access for price updates as requested
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false, 'message'=>'POST required']); exit; }
            $id = intval($_POST['id'] ?? 0);
            $price = floatval($_POST['price'] ?? 0);
            
            if (!$id || $price <= 0) { echo json_encode(['ok'=>false, 'message'=>'Invalid ID or Price']); exit; }
            
            try {
                $sql = "UPDATE medicine_master_data SET unit_price = :price WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute(['price'=>$price, 'id'=>$id]);
                
                // Mirror to SQLite if offline sync table allows, or just direct exec if HybridPDO handles it
                // Since it's a write query, HybridPDO will handle synchronization.
                
                logAction('MEDICINE_PRICE_UPDATE', "Price updated for medicine ID $id to $price");
                echo json_encode(['ok'=>true, 'message'=>'Price updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
            }
            exit;
            break;

        case 'suggest_specialty':
            header('Content-Type: application/json');
            if (!isLoggedIn()) { echo json_encode(['ok'=>false, 'message'=>'Unauthorized']); exit; }
            $s = trim($_GET['symptom'] ?? '');
            if (strlen($s) < 2) { echo json_encode([]); exit; }
            
            try {
                $stmt = $db->prepare("SELECT ds.name as specialty 
                                    FROM symptoms sy 
                                    JOIN symptom_specialty_mapping m ON sy.id = m.symptom_id 
                                    JOIN doctor_specialties ds ON m.specialty_id = ds.id 
                                    WHERE sy.name LIKE :s 
                                    ORDER BY m.priority DESC LIMIT 3");
                $stmt->execute(['s' => "%$s%"]);
                $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo json_encode(['ok'=>true, 'specialties'=>$results]);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
            }
            exit;
            break;

        case 'run_tool':
            // Run a maintenance/diagnostic script from private/tools/ via web (Admin only)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>false,'message'=>'Invalid CSRF token']); exit;
                }
                $role = strtolower($_SESSION['role'] ?? '');
                if (!in_array($role, ['admin','root'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>false,'message'=>'Unauthorized']); exit;
                }
                $file = basename($_POST['tool_file'] ?? '');
                if ($file === '') {
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>false,'message'=>'No tool specified']); exit;
                }
                $toolsDir = realpath(__DIR__ . '/private/tools');
                $candidate = $toolsDir . DIRECTORY_SEPARATOR . $file;
                if (!$toolsDir || !file_exists($candidate) || strpos(realpath($candidate), $toolsDir) !== 0) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>false,'message'=>'Tool not found']); exit;
                }

                // Use the discovered PHP binary from config
                $phpBin = PHP_BIN;

                $cmd = '"' . $phpBin . '" -f "' . $candidate . '" 2>&1';
                
                try {
                    $output = shell_exec($cmd);
                    logAction('RUN_TOOL', "Tool executed: $file by user " . ($_SESSION['username'] ?? 'unknown'));
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>true, 'output'=>$output]); exit;
                } catch (Exception $e) {
                    logAction('RUN_TOOL_ERROR', $e->getMessage());
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>false, 'message'=>'Execution failed', 'error'=>$e->getMessage()]); exit;
                }
            }
            header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Invalid request']); exit;
            break;

        case 'upload_image':
            // TinyMCE image upload endpoint. Returns JSON with {location: '/path/to/img'}
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Allow doctor or admin to upload
                $role = strtolower($_SESSION['role'] ?? '');
                if (!in_array($role, ['doctor','admin','root'])) {
                    header('HTTP/1.1 403 Forbidden'); echo json_encode(['error'=>'Unauthorized']); exit;
                }
                // Prefer CSRF verification for form posts (TinyMCE may not include it). If present, verify.
                if (isset($_POST['csrf_token']) && !verify_csrf()) {
                    header('HTTP/1.1 403 Forbidden'); echo json_encode(['error'=>'Invalid CSRF token']); exit;
                }
                if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    header('Content-Type: application/json'); echo json_encode(['error'=>'No file uploaded']); exit;
                }
                $f = $_FILES['file'];
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array(strtolower($ext), $allowed)) {
                    header('Content-Type: application/json'); echo json_encode(['error'=>'File type not allowed']); exit;
                }
                $dir = __DIR__ . '/uploads/prescriptions_images/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fileName = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $dir . $fileName;
                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    header('Content-Type: application/json'); echo json_encode(['error'=>'Failed to move uploaded file']); exit;
                }
                $url = rtrim(SITE_URL, '/') . '/uploads/prescriptions_images/' . $fileName;
                header('Content-Type: application/json'); echo json_encode(['location' => $url]); exit;
            }
            header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid request']); exit;
            break;

        case 'send_appointment_update':
            // Send a custom update message to appointment patient (and optionally doctor)
            if ($_POST && isset($_POST['appointment_id']) && isset($_POST['update_message'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/appointments.php');
                }
                $appointment_id = sanitizeInput($_POST['appointment_id']);
                $message = sanitizeInput($_POST['update_message']);
                try {
                    $q = $db->prepare('SELECT a.appointment_id, p.first_name AS pfn, p.last_name AS pln, p.email AS pemail, d.first_name AS dfn, d.last_name AS dln, d.email AS demail, a.appointment_date, a.appointment_time FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id LEFT JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.appointment_id = :id LIMIT 1');
                    $q->bindParam(':id', $appointment_id);
                    $q->execute();
                    if ($q->rowCount() == 0) {
                        $_SESSION['error'] = 'Appointment not found.';
                        redirect('pages/appointments.php');
                    }
                    $row = $q->fetch(PDO::FETCH_ASSOC);
                    $patientEmail = $row['pemail'] ?? '';
                    $doctorEmail = $row['demail'] ?? '';
                    $patientName = trim(($row['pfn'] ?? '') . ' ' . ($row['pln'] ?? ''));

                    $subject = SITE_NAME . ' - Appointment Update';
                    $body  = '<p>Hello ' . htmlspecialchars($patientName) . ',</p>';
                    $body .= '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
                    $body .= '<p><strong>Appointment:</strong> ' . htmlspecialchars($row['appointment_date'] ?? '') . ' at ' . htmlspecialchars($row['appointment_time'] ?? '') . '</p>';
                    $body .= '<p>Regards,<br>' . SITE_NAME . '</p>';

                    $sent = false;
                    if (!empty($patientEmail)) {
                        $sent = sendMail($patientEmail, $subject, $body);
                    }
                    if ($sent) {
                        $_SESSION['success'] = 'Update sent to patient.';
                    } else {
                        $_SESSION['error'] = 'Failed to send update to patient.';
                    }
                    redirect('pages/appointments.php');
                } catch (Exception $e) {
                    logAction('SEND_APPOINTMENT_UPDATE_ERROR', $e->getMessage());
                    $_SESSION['error'] = 'Failed to send appointment update.';
                    redirect('pages/appointments.php');
                }
            }
            redirect('pages/appointments.php');
            break;
            
        case 'book_appointment':
            if ($_POST) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/book_appointment.php');
                }
                $patient_id = sanitizeInput($_POST['patient_id']);
                $doctor_id = sanitizeInput($_POST['doctor_id']);
                $appointment_date = sanitizeInput($_POST['appointment_date']);
                $appointment_time = sanitizeInput($_POST['appointment_time']);
                $consultation_type = sanitizeInput($_POST['consultation_type']);
                $notes = sanitizeInput($_POST['notes']);
                
                // Check if time slot is available
                $check_query = "SELECT appointment_id FROM appointments 
                               WHERE doctor_id = :doctor_id 
                               AND appointment_date = :appointment_date 
                               AND appointment_time = :appointment_time 
                               AND status != 'cancelled'";
                
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':doctor_id', $doctor_id);
                $check_stmt->bindParam(':appointment_date', $appointment_date);
                $check_stmt->bindParam(':appointment_time', $appointment_time);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Selected time slot is not available. Please choose another time.";
                    redirect('pages/book_appointment.php');
                }
                
                $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, consultation_type, notes) 
                         VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :consultation_type, :notes)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':patient_id', $patient_id);
                $stmt->bindParam(':doctor_id', $doctor_id);
                $stmt->bindParam(':appointment_date', $appointment_date);
                $stmt->bindParam(':appointment_time', $appointment_time);
                $stmt->bindParam(':consultation_type', $consultation_type);
                $stmt->bindParam(':notes', $notes);
                
                // Start transaction to ensure consistent slot check + insert and assign per-day serial
                $db->beginTransaction();
                try {
                    if ($stmt->execute()) {
                        // allocate per-day serial safely using appointment_counters
                        $serial = 1;
                        // Use doctor-specific counters to give each doctor their own serial per day
                        $cnt = $db->prepare('SELECT last_serial FROM appointment_counters WHERE `date` = :date AND doctor_id = :did FOR UPDATE');
                        $cnt->bindParam(':date', $appointment_date);
                        $cnt->bindParam(':did', $doctor_id);
                        $cnt->execute();
                        if ($cnt->rowCount() > 0) {
                            $crow = $cnt->fetch(PDO::FETCH_ASSOC);
                            $serial = intval($crow['last_serial']) + 1;
                            $up = $db->prepare('UPDATE appointment_counters SET last_serial = :s WHERE `date` = :date AND doctor_id = :did');
                            $up->bindParam(':s', $serial);
                            $up->bindParam(':date', $appointment_date);
                            $up->bindParam(':did', $doctor_id);
                            $up->execute();
                        } else {
                            $serial = 1;
                            $insc = $db->prepare('INSERT INTO appointment_counters (`date`, doctor_id, last_serial) VALUES (:date, :did, :s)');
                            $insc->bindParam(':date', $appointment_date);
                            $insc->bindParam(':did', $doctor_id);
                            $insc->bindParam(':s', $serial);
                            $insc->execute();
                        }

                        // update appointment record with serial
                        $appointment_id = $db->lastInsertId();
                        $uap = $db->prepare('UPDATE appointments SET appointment_serial = :serial WHERE appointment_id = :id');
                        $uap->bindParam(':serial', $serial);
                        $uap->bindParam(':id', $appointment_id);
                        $uap->execute();

                        $db->commit();
                    } else {
                        throw new Exception("Failed to book appointment");
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                    logAction("APPOINTMENT_BOOKED", "Appointment booked for patient ID: $patient_id with doctor ID: $doctor_id");
                    $_SESSION['success'] = "Appointment booked successfully!";

                    // Notify patient and doctor by email (if emails available)
                    // Fetch patient info
                    $p = $db->prepare('SELECT first_name, last_name, email FROM patients WHERE patient_id = :id');
                    $p->bindParam(':id', $patient_id);
                    $p->execute();
                    $patient = $p->fetch(PDO::FETCH_ASSOC);

                    // Fetch doctor info
                    $d = $db->prepare('SELECT first_name, last_name, email FROM doctors WHERE doctor_id = :id');
                    $d->bindParam(':id', $doctor_id);
                    $d->execute();
                    $doctor = $d->fetch(PDO::FETCH_ASSOC);

                    $patientName = ($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '');
                    $doctorName = ($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '');

                    if ($patient && !empty($patient['email'])) {
                        sendAppointmentNotificationToPatient($patient['email'], $patientName, $doctorName, $appointment_date, $appointment_time, $notes);
                    }
                    if ($doctor && !empty($doctor['email'])) {
                        sendAppointmentNotificationToDoctor($doctor['email'], $doctorName, $patientName, $appointment_date, $appointment_time, $notes);
                    }

                    // After successful booking, redirect to the appointment preview/print page
                    redirect('pages/print_appointment.php?id=' . $appointment_id);
                } // end if ($_POST)
            break;
                case 'edit_appointment':
                    if ($_POST && isset($_POST['appointment_id'])) {
                        if (!verify_csrf()) {
                            $_SESSION['error'] = 'Invalid request token (CSRF).';
                            redirect('pages/appointments.php');
                        }
                        $appointment_id = intval($_POST['appointment_id']);
                        $patient_id = isset($_POST['patient_id']) ? sanitizeInput($_POST['patient_id']) : null;
                        $doctor_id = isset($_POST['doctor_id']) ? sanitizeInput($_POST['doctor_id']) : null;
                        $appointment_date = isset($_POST['appointment_date']) ? sanitizeInput($_POST['appointment_date']) : null;
                        $appointment_time = isset($_POST['appointment_time']) ? sanitizeInput($_POST['appointment_time']) : null;
                        $consultation_type = isset($_POST['consultation_type']) ? sanitizeInput($_POST['consultation_type']) : null;
                        $symptoms = isset($_POST['symptoms']) ? sanitizeInput($_POST['symptoms']) : null;
                        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : null;

                        $query = 'UPDATE appointments SET patient_id = :patient_id, doctor_id = :doctor_id, appointment_date = :appointment_date, appointment_time = :appointment_time, consultation_type = :consultation_type, symptoms = :symptoms, notes = :notes WHERE appointment_id = :appointment_id';
                        $u = $db->prepare($query);
                        $u->bindParam(':patient_id', $patient_id);
                        $u->bindParam(':doctor_id', $doctor_id);
                        $u->bindParam(':appointment_date', $appointment_date);
                        $u->bindParam(':appointment_time', $appointment_time);
                        $u->bindParam(':consultation_type', $consultation_type);
                        $u->bindParam(':symptoms', $symptoms);
                        $u->bindParam(':notes', $notes);
                        $u->bindParam(':appointment_id', $appointment_id);

                        if ($u->execute()) {
                            logAction('APPOINTMENT_EDITED', "Appointment $appointment_id edited by " . ($_SESSION['username'] ?? 'System'));
                            $_SESSION['success'] = 'Appointment updated successfully.';
                        } else {
                            $_SESSION['error'] = 'Failed to update appointment.';
                        }
                    }
                    redirect('pages/appointments.php');
                    break;
            
        case 'approve_waiting':
            if ($_POST && isset($_POST['waiting_id'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/waiting_admin.php');
                }
                $waiting_id = intval($_POST['waiting_id']);
                $doctor_id = intval($_POST['doctor_id']);
                $appointment_date = sanitizeInput($_POST['appointment_date']);
                $appointment_time = sanitizeInput($_POST['appointment_time']);

                // fetch waiting entry and patient
                $q = $db->prepare('SELECT patient_id FROM waiting_list WHERE waiting_id = :id LIMIT 1');
                $q->bindParam(':id', $waiting_id);
                $q->execute();
                if ($q->rowCount() == 0) {
                    $_SESSION['error'] = 'Waiting entry not found.';
                    redirect('pages/waiting_admin.php');
                }
                $w = $q->fetch(PDO::FETCH_ASSOC);
                $patient_id = $w['patient_id'];

                // Check slot availability
                $check = $db->prepare('SELECT appointment_id FROM appointments WHERE doctor_id = :doctor_id AND appointment_date = :d AND appointment_time = :t AND status != "cancelled"');
                $check->bindParam(':doctor_id', $doctor_id);
                $check->bindParam(':d', $appointment_date);
                $check->bindParam(':t', $appointment_time);
                $check->execute();
                if ($check->rowCount() > 0) {
                    $_SESSION['error'] = 'Selected time slot is not available.';
                    redirect('pages/waiting_admin.php');
                }

                // create appointment inside a transaction and allocate per-day serial
                $db->beginTransaction();
                try {
                    $ins = $db->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, consultation_type, notes, status) VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :consultation_type, :notes, :status)');
                    $consultation_type = 'in-person';
                    $notes = 'Approved from waiting list';
                    $status = 'scheduled';
                    $ins->bindParam(':patient_id', $patient_id);
                    $ins->bindParam(':doctor_id', $doctor_id);
                    $ins->bindParam(':appointment_date', $appointment_date);
                    $ins->bindParam(':appointment_time', $appointment_time);
                    $ins->bindParam(':consultation_type', $consultation_type);
                    $ins->bindParam(':notes', $notes);
                    $ins->bindParam(':status', $status);
                    if (!$ins->execute()) {
                        throw new Exception('Failed to create appointment.');
                    }
                    $appointment_id = $db->lastInsertId();

                    // allocate per-day serial safely (per-doctor)
                    $serial = 1;
                    $cnt = $db->prepare('SELECT last_serial FROM appointment_counters WHERE `date` = :date AND doctor_id = :did FOR UPDATE');
                    $cnt->bindParam(':date', $appointment_date);
                    $cnt->bindParam(':did', $doctor_id);
                    $cnt->execute();
                    if ($cnt->rowCount() > 0) {
                        $crow = $cnt->fetch(PDO::FETCH_ASSOC);
                        $serial = intval($crow['last_serial']) + 1;
                        $up = $db->prepare('UPDATE appointment_counters SET last_serial = :s WHERE `date` = :date AND doctor_id = :did');
                        $up->bindParam(':s', $serial);
                        $up->bindParam(':date', $appointment_date);
                        $up->bindParam(':did', $doctor_id);
                        $up->execute();
                    } else {
                        $serial = 1;
                        $insc = $db->prepare('INSERT INTO appointment_counters (`date`, doctor_id, last_serial) VALUES (:date, :did, :s)');
                        $insc->bindParam(':date', $appointment_date);
                        $insc->bindParam(':did', $doctor_id);
                        $insc->bindParam(':s', $serial);
                        $insc->execute();
                    }

                    // update appointment with serial
                    $uap = $db->prepare('UPDATE appointments SET appointment_serial = :serial WHERE appointment_id = :id');
                    $uap->bindParam(':serial', $serial);
                    $uap->bindParam(':id', $appointment_id);
                    $uap->execute();

                    // update waiting_list
                    $u = $db->prepare('UPDATE waiting_list SET status = :status, taken_by = :taken_by, appointment_id = :appointment_id WHERE waiting_id = :id');
                    $taken_by = $_SESSION['user_id'] ?? null;
                    $ustatus = 'processed';
                    $u->bindParam(':status', $ustatus);
                    $u->bindParam(':taken_by', $taken_by);
                    $u->bindParam(':appointment_id', $appointment_id);
                    $u->bindParam(':id', $waiting_id);
                    $u->execute();

                    $db->commit();

                    // fetch patient and doctor info for notifications
                    $p = $db->prepare('SELECT first_name, last_name, email FROM patients WHERE patient_id = :id');
                    $p->bindParam(':id', $patient_id); $p->execute(); $patient = $p->fetch(PDO::FETCH_ASSOC);
                    $d = $db->prepare('SELECT first_name, last_name, email FROM doctors WHERE doctor_id = :id');
                    $d->bindParam(':id', $doctor_id); $d->execute(); $doctor = $d->fetch(PDO::FETCH_ASSOC);

                    $patientName = ($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '');
                    $doctorName = ($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '');

                    if ($patient && !empty($patient['email'])) {
                        sendAppointmentNotificationToPatient($patient['email'], $patientName, $doctorName, $appointment_date, $appointment_time, $notes);
                    }
                    if ($doctor && !empty($doctor['email'])) {
                        sendAppointmentNotificationToDoctor($doctor['email'], $doctorName, $patientName, $appointment_date, $appointment_time, $notes);
                    }

                    logAction('WAITING_APPROVED', "Waiting $waiting_id approved and appointment $appointment_id created by user " . ($_SESSION['username'] ?? 'System'));
                    $_SESSION['success'] = 'Patient approved and appointment created.';
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
            }
            redirect('pages/waiting_admin.php');
            break;
        case 'update_appointment_status':
            if ($_POST && isset($_POST['appointment_id']) && isset($_POST['status'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/appointments.php');
                }
                $appointment_id = sanitizeInput($_POST['appointment_id']);
                $status = sanitizeInput($_POST['status']);
                
                $query = "UPDATE appointments SET status = :status WHERE appointment_id = :appointment_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':appointment_id', $appointment_id);
                
                if ($stmt->execute()) {
                    logAction("APPOINTMENT_STATUS_UPDATED", "Appointment $appointment_id status changed to $status");
                    $_SESSION['success'] = "Appointment status updated successfully!";
                } else {
                    throw new Exception("Failed to update appointment status");
                }
            }
            redirect('pages/appointments.php');
            break;
        case 'save_recurrence':
            // Save a recurrence rule (create only for now)
            if ($_POST) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid request token (CSRF).';
                    redirect('pages/manage_recurrences.php');
                }
                // Only allow admins/receptionists
                checkRole(['admin','receptionist']);

                $doctor_id = sanitizeInput($_POST['doctor_id'] ?? '');
                $patient_id = sanitizeInput($_POST['patient_id'] ?? '');
                $frequency = sanitizeInput($_POST['frequency'] ?? 'weekly');
                $interval = intval($_POST['interval'] ?? 1);
                $by_weekdays = sanitizeInput($_POST['by_weekdays'] ?? '');
                $by_monthday = sanitizeInput($_POST['by_monthday'] ?? '');
                $appointment_time = sanitizeInput($_POST['appointment_time'] ?? '');
                $start_date = sanitizeInput($_POST['start_date'] ?? '');
                $end_date = sanitizeInput($_POST['end_date'] ?? null);
                $occurrences = isset($_POST['occurrences']) && $_POST['occurrences'] !== '' ? intval($_POST['occurrences']) : null;
                $duration_minutes = intval($_POST['duration_minutes'] ?? 15);
                $consultation_type = sanitizeInput($_POST['consultation_type'] ?? 'recurring');
                $notes = sanitizeInput($_POST['notes'] ?? '');
                $active = isset($_POST['active']) ? 1 : 0;

                try {
                    $ins = $db->prepare('INSERT INTO recurrence_rules (doctor_id, patient_id, frequency, `interval`, by_weekdays, by_monthday, start_date, end_date, occurrences, appointment_time, duration_minutes, consultation_type, notes, created_by, created_at, active) VALUES (:doctor_id, :patient_id, :frequency, :interval, :by_weekdays, :by_monthday, :start_date, :end_date, :occurrences, :appointment_time, :duration_minutes, :consultation_type, :notes, :created_by, NOW(), :active)');
                    $ins->bindParam(':doctor_id', $doctor_id);
                    $ins->bindParam(':patient_id', $patient_id);
                    $ins->bindParam(':frequency', $frequency);
                    $ins->bindParam(':interval', $interval);
                    $ins->bindParam(':by_weekdays', $by_weekdays);
                    $ins->bindParam(':by_monthday', $by_monthday);
                    $ins->bindParam(':start_date', $start_date);
                    $ins->bindParam(':end_date', $end_date);
                    $ins->bindParam(':occurrences', $occurrences);
                    $ins->bindParam(':appointment_time', $appointment_time);
                    $ins->bindParam(':duration_minutes', $duration_minutes);
                    $ins->bindParam(':consultation_type', $consultation_type);
                    $ins->bindParam(':notes', $notes);
                    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                    $ins->bindParam(':created_by', $created_by);
                    $ins->bindParam(':active', $active);

                    if ($ins->execute()) {
                        $_SESSION['success'] = 'Recurrence rule saved.';
                        logAction('RECURRENCE_CREATED', 'Rule created by user ' . ($_SESSION['username'] ?? 'System'));
                    } else {
                        $err = $ins->errorInfo();
                        throw new Exception('DB insert failed: ' . ($err[2] ?? 'unknown'));
                    }
                } catch (Exception $e) {
                    logAction('RECURRENCE_SAVE_ERROR', $e->getMessage());
                    $_SESSION['error'] = 'Failed to save recurrence: ' . $e->getMessage();
                }
            }
            redirect('pages/manage_recurrences.php');
            break;
            
        case 'logout':
            if (isset($_SESSION['user_id'])) {
                $uid = $_SESSION['user_id'];
                $username = $_SESSION['username'] ?? 'Unknown';
                try {
                    // Force clear last_activity and set last_logout
                    $up_logout = $db->prepare("UPDATE users SET last_logout = NOW(), last_activity = NULL WHERE user_id = :uid");
                    $up_logout->execute(['uid' => $uid]);

                    // Close session log
                    if (isset($_SESSION['login_log_id'])) {
                        $up_session = $db->prepare("UPDATE user_logins SET logout_time = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW()), status = 'logged_out' WHERE id = :lid");
                        $up_session->execute(['lid' => $_SESSION['login_log_id']]);
                    } else {
                        // Backup: close any active session for this user
                        $db->prepare("UPDATE user_logins SET logout_time = NOW(), status = 'logged_out' WHERE user_id = :uid AND status = 'active'")
                           ->execute(['uid' => $uid]);
                    }
                    logAction("USER_LOGOUT", "User logged out: $username");
                    
                    // Notify Admins and Receptionists
                    $logoutMsg = "User $username has logged out.";
                    notifyRoles($db, ['admin', 'receptionist'], 'auth', 'Staff Logout', $logoutMsg);
                } catch (Exception $e) { /* ignore */ }
            }
            
            session_unset();
            session_destroy();
            redirect('pages/login.php');
            break;

        case 'notifications_unread_count':
            // Return unread notification count for current user (AJAX)
            header('Content-Type: application/json');
            if (!isLoggedIn()) { echo json_encode(['ok'=>false,'unread_count'=>0]); exit; }
            $uid = $_SESSION['user_id'];
            $cnt = getUnreadNotificationCount($db, $uid);
            echo json_encode(['ok'=>true,'unread_count'=>intval($cnt)]);
            exit;
            break;

        case 'log_print':
            // Background log for print actions - creates a notification and logs action
            header('Content-Type: application/json');
            if (!isLoggedIn()) { echo json_encode(['ok'=>false,'message'=>'Not logged in']); exit; }
            $uid = $_SESSION['user_id'];
            $appointment_id = isset($_POST['appointment_id']) ? sanitizeInput($_POST['appointment_id']) : (isset($_POST['id']) ? sanitizeInput($_POST['id']) : null);
            if (empty($appointment_id)) { echo json_encode(['ok'=>false,'message'=>'Missing appointment id']); exit; }
            try {
                logAction('PRINT_ACTION', 'User ' . ($_SESSION['username'] ?? $uid) . ' printed appointment ' . $appointment_id);
                createNotification($db, $uid, 'print', 'Printed appointment', 'Appointment #' . $appointment_id . ' printed by ' . ($_SESSION['username'] ?? 'user'), ['appointment_id' => $appointment_id]);
                echo json_encode(['ok'=>true]);
            } catch (Exception $e) {
                echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
            }
            exit;
            break;
            
        case 'kill_session':
            checkRole(['admin', 'root']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid CSRF token.';
                    redirect('pages/session_history.php');
                }
                $login_id = intval($_POST['login_id']);
                try {
                    $stmt = $db->prepare("UPDATE user_logins SET status = 'killed', logout_time = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW()) WHERE id = :id AND status = 'active'");
                    $stmt->execute(['id' => $login_id]);
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['success'] = 'Session forcefully terminated.';
                        logAction("KILL_SESSION", "Admin killed session ID: $login_id");
                    } else {
                        $_SESSION['error'] = 'Session is not active or could not be found.';
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Failed to kill session: ' . $e->getMessage();
                }
            }
            redirect('pages/session_history.php');
            break;

        case 'kill_all_user_sessions':
            checkRole(['admin', 'root']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid CSRF token.';
                    redirect('pages/session_history.php');
                }
                $uid = intval($_POST['user_id']);
                try {
                    $stmt = $db->prepare("UPDATE user_logins SET status = 'killed', logout_time = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW()) WHERE user_id = :uid AND status = 'active'");
                    $stmt->execute(['uid' => $uid]);
                    $_SESSION['success'] = $stmt->rowCount() . ' active sessions terminated for user ID: ' . $uid;
                    logAction("KILL_ALL_SESSIONS", "Admin killed all sessions for user ID: $uid");
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Failed to terminate sessions: ' . $e->getMessage();
                }
            }
            redirect('pages/session_history.php');
            break;

        case 'cleanup_inactive_sessions':
            checkRole(['admin', 'root']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) {
                    $_SESSION['error'] = 'Invalid CSRF token.';
                    redirect('pages/session_history.php');
                }
                try {
                    // Find active sessions where the user's last_activity is older than 1 hour
                    $stmt = $db->prepare("
                        UPDATE user_logins ul
                        JOIN users u ON ul.user_id = u.user_id
                        SET ul.status = 'auto_logged_out', ul.logout_time = NOW(), ul.duration_seconds = TIMESTAMPDIFF(SECOND, ul.login_time, NOW())
                        WHERE ul.status = 'active' AND TIMESTAMPDIFF(SECOND, u.last_activity, NOW()) > 3600
                    ");
                    $stmt->execute();
                    $cleaned = $stmt->rowCount();
                    $_SESSION['success'] = "Cleanup complete. Marked $cleaned inactive sessions as auto-logged out.";
                    logAction("CLEANUP_SESSIONS", "Admin triggered cleanup. $cleaned sessions logged out.");
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Failed to cleanup sessions: ' . $e->getMessage();
                }
            }
            redirect('pages/session_history.php');
            break;

        default:
            logAction("PROCESS_ERROR", "Invalid action: $action");
            $_SESSION['error'] = "Invalid action requested.";
            redirect('index.php');
            break;
    }
    
} catch (PDOException $e) {
    // If the caller expects JSON (AJAX), return JSON error instead of redirecting
    $isAjax = (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
    );
    
    logAction("PROCESS_DB_ERROR", "Database error in $action: " . $e->getMessage());
    
    $errorMsg = "Database error occurred. Please try again.";
    
    // Check for duplicate entry error (MySQL 1062)
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        if ($action == 'add_patient' && strpos($e->getMessage(), 'email') !== false) {
            $errorMsg = "A patient with this email address already exists.";
        } else if ($action == 'add_user' && strpos($e->getMessage(), 'username') !== false) {
            $errorMsg = "This username is already taken.";
        } else if ($action == 'add_user' && strpos($e->getMessage(), 'email') !== false) {
            $errorMsg = "This email is already registered to another user.";
        } else {
            $errorMsg = "Duplicate entry detected. Please check your information.";
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $errorMsg, 'toast' => true]);
        exit;
    }
    $_SESSION['error'] = $errorMsg;
    redirect('index.php');
} catch (Exception $e) {
    $isAjax = (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
        (isset($_GET['ajax']) && $_GET['ajax'] == '1')
    );
    logAction("PROCESS_ERROR", "Error in $action: " . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'toast' => true]);
        exit;
    }
    $_SESSION['error'] = $e->getMessage();
    redirect('index.php');
}
?>