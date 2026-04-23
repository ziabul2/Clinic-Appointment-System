<?php
$page_title = "Reset Password";
require_once '../includes/header.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['error'] = 'Invalid or missing reset token.';
    redirect('password_reset_request.php');
}

// We'll show the form; actual verification happens in process.php when submitted
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-info text-white text-center">
                <h4><i class="fas fa-key"></i> Set New Password</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="../process.php?action=perform_password_reset">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-info">Set Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>