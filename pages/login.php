<?php
// Ensure config is loaded so we can check session/login before any output
$page_title = "Login";
require_once __DIR__ . '/../config/config.php';

// If already logged in, redirect before sending any HTML
if (isLoggedIn()) {
    redirect('pages/dashboard.php');
}

// Now include header which outputs HTML
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4>Login</h4>
            </div>
            <div class="card-body">
                <!-- Flash messages handled centrally in header as floating notifications -->

                <form method="POST" action="../process.php?action=login" class="row g-3">
                    <?php echo csrf_input(); ?>
                    <div class="col-12">
                        <label class="form-label">Username or Email</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div>
                            <a href="password_reset_request.php">Forgot password?</a>
                        </div>
                        <div>
                            <button class="btn btn-primary" type="submit">Login</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>