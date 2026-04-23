<?php
$page_title = "Password Reset Request";
require_once '../includes/header.php';

// If logged in, redirect to dashboard
if (isLoggedIn()) redirect('dashboard.php');
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark text-center">
                <h4><i class="fas fa-unlock-alt"></i> Request Password Reset</h4>
            </div>
            <div class="card-body">
                <p>Enter your account email and we'll send a secure password reset link.</p>
                <form method="POST" action="../process.php?action=request_password_reset">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-warning">Send Reset Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>