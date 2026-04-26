<?php
$page_title = "My Profile";
require_once '../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');

$user_id = $_SESSION['user_id'];
$doctor_id = $_SESSION['doctor_id'] ?? null;
$error = null; $success = null;

try {
    // Load user record
    $u = $db->prepare('SELECT * FROM users WHERE user_id = :uid LIMIT 1');
    $u->bindParam(':uid', $user_id);
    $u->execute();
    $user = $u->fetch(PDO::FETCH_ASSOC);

    $doctor = null;
    if ($doctor_id) {
        $d = $db->prepare('SELECT * FROM doctors WHERE doctor_id = :did LIMIT 1');
        $d->bindParam(':did', $doctor_id);
        $d->execute();
        $doctor = $d->fetch(PDO::FETCH_ASSOC);
    }

    if ($_POST) {
        if (!verify_csrf()) {
            $error = 'Invalid request token (CSRF).';
        }
        // allow updating profile picture if doctor
        if (empty($error) && isset($_FILES['profile_picture']) && !empty($_FILES['profile_picture']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/users/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_profile = 'usr_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $upload_dir . $new_profile;
            if (@move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                // update users table
                $up = $db->prepare('UPDATE users SET profile_picture = :p WHERE user_id = :uid');
                $up->bindParam(':p', $new_profile);
                $up->bindParam(':uid', $user_id);
                $up->execute();
                $success = 'Profile picture updated.';
                // reload user data
                $u->execute(); $user = $u->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to upload file.';
            }
        }
        // allow updating basic user info (display name and personal details)
        if (empty($error) && isset($_POST['display_name'])) {
            $display = sanitizeInput($_POST['display_name']);
            $first = sanitizeInput($_POST['first_name'] ?? '');
            $last = sanitizeInput($_POST['last_name'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $address = sanitizeInput($_POST['address'] ?? '');

            $uu = $db->prepare('UPDATE users SET username = :un, first_name = :fn, last_name = :ln, phone = :ph, address = :ad WHERE user_id = :uid');
            $uu->bindParam(':un', $display);
            $uu->bindParam(':fn', $first);
            $uu->bindParam(':ln', $last);
            $uu->bindParam(':ph', $phone);
            $uu->bindParam(':ad', $address);
            $uu->bindParam(':uid', $user_id);
            $uu->execute();
            $_SESSION['username'] = $display;
            $success = ($success ? $success . ' ' : '') . 'Profile updated.';
            // reload user
            $u->execute(); $user = $u->fetch(PDO::FETCH_ASSOC);
        }
    }

} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}
?>

<div class="list-container">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i> My Profile Settings</h5>
        <div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
    <div class="list-body">
        <?php if ($error): ?><div class="alert alert-danger shadow-sm"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success shadow-sm"><?php echo $success; ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 mb-4" style="background: rgba(255,255,255,0.4); border-radius: 12px;">
                    <div class="card-body p-4">
                        <form method="post" enctype="multipart/form-data">
                            <?php echo csrf_input(); ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Display Name</label>
                                    <input class="form-control" name="display_name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Phone Number</label>
                                    <input class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">First Name</label>
                                    <input class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Last Name</label>
                                    <input class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Profile Picture</label>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/../uploads/users/' . $user['profile_picture'])): ?>
                                        <img src="../uploads/users/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="rounded shadow-sm" style="width:80px;height:80px;object-fit:cover; border: 3px solid #fff;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center shadow-sm" style="width:80px;height:80px; color: #ced4da;">
                                            <i class="fas fa-user fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <input type="file" class="form-control form-control-sm" name="profile_picture" accept="image/*">
                                        <div class="small text-muted mt-1">Recommended size: 200x200px</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-primary px-4" type="submit">Update Information</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4" style="background: rgba(255,255,255,0.4); border-radius: 12px;">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0 text-uppercase small text-primary"><i class="fas fa-info-circle me-1"></i> Account Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="text-muted small">Username:</span>
                            <div class="fw-bold"><?php echo htmlspecialchars($user['username'] ?? ''); ?></div>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Access Role:</span>
                            <div><span class="badge bg-info-soft text-info"><?php echo ucfirst(htmlspecialchars($user['role'] ?? '')); ?></span></div>
                        </div>
                        <?php if ($doctor): ?>
                            <div>
                                <span class="text-muted small">Linked Doctor Profile:</span>
                                <div class="fw-bold text-primary">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4" style="background: rgba(255,255,255,0.4); border-radius: 12px;">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0 text-uppercase small text-success"><i class="fas fa-cog me-1"></i> Application Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-bold small">Show Footer</div>
                                <div class="text-muted smaller">Toggle the visibility of the bottom footer section.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="footerVisibilityToggle" checked>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0" style="background: rgba(255,255,255,0.4); border-radius: 12px;">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0 text-uppercase small text-warning"><i class="fas fa-shield-alt me-1"></i> Security</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="../process.php?action=change_password">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Current Password</label>
                                <input type="password" name="current_password" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">New Password</label>
                                <input type="password" name="new_password" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control form-control-sm" required>
                            </div>
                            <button class="btn btn-outline-warning btn-sm w-100" type="submit">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const footerToggle = document.getElementById('footerVisibilityToggle');
    
    // Load current state from localStorage
    const hideFooter = localStorage.getItem('hide_footer') === 'true';
    footerToggle.checked = !hideFooter;

    footerToggle.addEventListener('change', function() {
        const isVisible = this.checked;
        const hide = !isVisible;
        
        // Save to localStorage
        localStorage.setItem('hide_footer', hide);
        
        // Trigger event for script.js to react immediately
        document.dispatchEvent(new CustomEvent('clinic:footerVisibilityChanged', { detail: { hide: hide } }));

        // Save to JSON database via AJAX
        const formData = new FormData();
        formData.append('name', 'hide_footer');
        formData.append('value', hide);
        formData.append('csrf_token', window.__CSRF_TOKEN);

        fetch('../ajax/settings_handler.php?action=save', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                if (window.flashNotify) flashNotify('success', 'Settings Updated', 'Footer visibility preference saved.');
            } else {
                console.error('Failed to save setting to server:', data.message);
            }
        })
        .catch(error => console.error('Error saving setting:', error));
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
