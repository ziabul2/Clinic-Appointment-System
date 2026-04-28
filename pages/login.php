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

<div class="login-container">
    <div class="card glass-card login-card shadow-lg">
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
/* Full screen background fix */
body {
    background-image: url('../assets/images/login_bg.png') !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    background-repeat: no-repeat !important;
    margin: 0;
    padding: 0;
    height: 100vh;
    overflow: hidden;
}

.login-page {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent !important;
    backdrop-filter: brightness(0.7); /* Slightly darken the background for better contrast */
}

.login-card {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.4) !important;
}
</style>

<?php 
// Pass a custom footer script to handle form validation
require_once '../includes/footer.php'; 
?>