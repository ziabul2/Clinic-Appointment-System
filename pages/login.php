<?php
/**
 * Premium Login Page
 * Features: Glassmorphism, background animations, and modern layout.
 */
$page_title = "Login";
$hide_nav = true; // Global header will hide navbar
$hide_footer = true; // Global footer will hide visual elements
$container_class = "login-page"; // Apply full-screen layout from style.css

require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../pages/dashboard.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-container" style="background-image: url('../assets/images/login_bg.png'); background-size: cover; background-position: center; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
    <!-- Overlay for the background image -->
    <div style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.4) 0%, rgba(10, 25, 41, 0.8) 100%); width: 100%; height: 100%; position: absolute; top: 0; left: 0; z-index: 1;"></div>

    <div class="card glass-card login-card" style="position: relative; z-index: 2; border: none;">
        <div class="login-logo">
            <i class="fas fa-clinic-medical"></i>
        </div>
        <h2 class="login-title">Welcome Back</h2>
        <p class="text-center text-muted mb-4">Please enter your credentials to access the clinic system.</p>

        <form method="POST" action="../process.php?action=login" class="needs-validation" novalidate>
            <?php echo csrf_input(); ?>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required style="background: rgba(255,255,255,0.8); border-radius: 10px;">
                <label for="username">Username or Email</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required style="background: rgba(255,255,255,0.8); border-radius: 10px;">
                <label for="password">Password</label>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label text-muted small" for="rememberMe">
                        Remember me
                    </label>
                </div>
                <a href="password_reset_request.php" class="small text-decoration-none text-primary fw-bold">Forgot Password?</a>
            </div>

            <button class="btn btn-primary w-100 btn-login mb-4" type="submit">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In
            </button>


        </form>
    </div>
</div>

<style>
/* Fix for the login-page background to use the image properly */
.login-page {
    background-image: url('../assets/images/login_bg.png');
    background-attachment: fixed;
}
/* Center the container properly */
body {
    background: #f8f9fa;
}
</style>

<?php 
// Pass a custom footer script to handle form validation
require_once '../includes/footer.php'; 
?>