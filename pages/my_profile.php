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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user"></i> My Profile</h1>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow mb-3">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input class="form-control" name="display_name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input class="form-control" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/../uploads/users/' . $user['profile_picture'])): ?>
                            <div class="mb-2"><img src="../uploads/users/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width:96px;height:96px;object-fit:cover;border-radius:6px;"></div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="profile_picture" accept="image/*">
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-light">Account Info</div>
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role'] ?? ''); ?></p>
                <?php if ($doctor): ?>
                    <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow mt-3">
            <div class="card-header bg-light">Change Password</div>
            <div class="card-body">
                <form method="post" action="../process.php?action=change_password">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
