<?php
$page_title = "Register";
require_once __DIR__ . '/../config/config.php';

// Public registration: create patient + user + waiting entry
if ($_POST) {
    if (!verify_csrf()) {
        $_SESSION['error'] = 'Invalid request token (CSRF).';
        redirect('pages/register.php');
    }

    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = isset($_POST['password']) ? sanitizeInput($_POST['password']) : null;

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $_SESSION['error'] = 'Name and email are required.';
        redirect('pages/register.php');
    }

    try {
        // Create patient record
        $p = $db->prepare('INSERT INTO patients (first_name, last_name, email, phone, created_at) VALUES (:fn, :ln, :email, :phone, NOW())');
        $p->bindParam(':fn', $first_name);
        $p->bindParam(':ln', $last_name);
        $p->bindParam(':email', $email);
        $p->bindParam(':phone', $phone);
        $p->execute();
        $patient_id = $db->lastInsertId();

        // Create users entry (role patient)
        $username = preg_replace('/[^a-z0-9_\.]/i', '', explode('@', $email)[0]) . rand(10,99);
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $random = bin2hex(random_bytes(8));
            $hash = password_hash($random, PASSWORD_DEFAULT);
        }

        $role = 'patient';
        $u = $db->prepare('INSERT INTO users (username, password, email, role, created_at) VALUES (:username, :password, :email, :role, NOW())');
        $u->bindParam(':username', $username);
        $u->bindParam(':password', $hash);
        $u->bindParam(':email', $email);
        $u->bindParam(':role', $role);
        $u->execute();
        $user_id = $db->lastInsertId();

        // Create a password reset token and email set-password link
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
        $ins->bindParam(':user_id', $user_id);
        $ins->bindParam(':token', $token);
        $ins->bindParam(':expires_at', $expires);
        $ins->execute();

        // Add to waiting list so receptionist can process
        $waitToken = bin2hex(random_bytes(16));
        $wl = $db->prepare('INSERT INTO waiting_list (patient_id, user_id, status, requested_at, token, notes) VALUES (:patient_id, :user_id, :status, :requested_at, :token, :notes)');
        $status = 'waiting';
        $now = date('Y-m-d H:i:s');
        $notes = 'Registered via public registration form.';
        $wl->bindParam(':patient_id', $patient_id);
        $wl->bindParam(':user_id', $user_id);
        $wl->bindParam(':status', $status);
        $wl->bindParam(':requested_at', $now);
        $wl->bindParam(':token', $waitToken);
        $wl->bindParam(':notes', $notes);
        $wl->execute();
        $waiting_id = $db->lastInsertId();

        // send set-password link and waiting confirmation
        if (!empty($email)) {
            sendPasswordResetLink($email, $username, $token);
            // send waiting confirmation email with link to view status (includes token)
            $statusUrl = rtrim(SITE_URL, '/') . '/pages/waiting_status.php?id=' . $waiting_id . '&token=' . urlencode($waitToken);
            $subject = SITE_NAME . ' - Registration Received';
            $body  = '<p>Hello '.htmlspecialchars($first_name).',</p>';
            $body .= '<p>Your registration was received. You are now in the waiting queue.</p>';
            $body .= '<p>View your waiting status here: <a href="'.htmlspecialchars($statusUrl).'">View status</a></p>';
            $body .= '<p>Regards,<br>'.SITE_NAME.'</p>';
            sendMail($email, $subject, $body);
        }

        $_SESSION['success'] = "Registration successful. Please check your email to set your password and view waiting status.";
        // Store waiting id so user can be redirected to waiting page immediately
        $_SESSION['waiting_id'] = $waiting_id;
        redirect('pages/waiting_status.php?id=' . $waiting_id . '&token=' . urlencode($waitToken));

    } catch (Exception $e) {
        logAction('REGISTER_ERROR', $e->getMessage());
        $_SESSION['error'] = 'Registration failed. Please try again.';
        redirect('pages/register.php');
    }
}

// Now include header and render page
require_once '../includes/header.php';

// Today's registrations count
$todayCount = 0;
try {
    $q = $db->prepare("SELECT COUNT(*) as cnt FROM patients WHERE DATE(created_at) = CURDATE()");
    $q->execute(); $r = $q->fetch(PDO::FETCH_ASSOC);
    $todayCount = intval($r['cnt']);
} catch (Exception $e) { /* ignore */ }

?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4>Register</h4>
                <div class="small">Today's registrations: <strong><?php echo $todayCount; ?></strong></div>
            </div>
            <div class="card-body">
                <!-- Flash messages handled centrally in header as floating notifications -->

                <form method="POST" action="" class="row g-3 needs-validation" novalidate>
                    <?php echo csrf_input(); ?>
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input class="form-control" name="last_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone (optional)</label>
                        <input class="form-control" name="phone">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Password (optional)</label>
                        <input type="password" class="form-control" name="password" placeholder="Leave blank to receive set-password link">
                    </div>
                    <div class="col-12 d-flex justify-content-end align-items-center">
                        <div>
                            <button class="btn btn-primary" type="submit">Register</button>
                            <a href="../index.php?login=" class="btn btn-link">Already have account? Login</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
