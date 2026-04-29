<?php
    // Footer content
?>
    </div> <!-- End container -->

    <?php if (!($hide_footer ?? false)): ?>
    <!-- Premium Modern Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="hospital-icon heartbeat me-2">
                                <i class="fas fa-hospital-alt fa-2x"></i>
                            </div>
                            <h4 class="mb-0 gradient-text"><?php echo SITE_NAME; ?></h4>
                        </div>
                        <p class="text-muted small slide-in">
                            Providing world-class healthcare management solutions with a focus on patient care and efficiency.
                        </p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="../pages/dashboard.php" class="footer-link">Dashboard</a></li>
                        <li class="mb-2"><a href="../pages/appointments.php" class="footer-link">Appointments</a></li>
                        <li class="mb-2"><a href="../pages/patients.php" class="footer-link">Patients</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="footer-link">Help Center</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Admin Info -->
                <div class="col-lg-4 col-md-6 text-lg-end">
                    <h6 class="text-white fw-bold mb-3">Administrator</h6>
                    <div class="admin-card d-inline-block text-start p-3 rounded-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <div class="badge glow p-2 rounded-circle">
                                    <i class="fas fa-user-shield fa-lg"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-white fw-bold small">Ziabul Islam</div>
                                <div class="text-muted smaller">System Administrator</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="footer-meta d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-3 mb-md-0">
                    <small class="text-muted fade-in">
                        &copy; <?php echo date('Y'); ?> <strong><?php echo SITE_NAME; ?></strong>. All rights reserved.
                    </small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-dark text-muted border border-secondary me-2">v2.5.0-premium</span>
                    <div class="social-links">
                        <a href="#" class="text-muted me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <button class="footer-toggle" id="footerToggleBtn" title="Toggle Footer">
            <i class="fas fa-chevron-down"></i>
        </button>
    </footer>
    <?php endif; ?>

    <?php if (isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'messenger.php'): ?>
        <!-- Floating Chat Button -->
        <button id="floatingChatBtn" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1040; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            <i class="fas fa-comment-dots fa-2x"></i>
            <span id="chatFloatingBadge" class="badge bg-danger rounded-pill shadow-sm border border-white" style="position:absolute; top: -2px; right: -2px; display:none;">0</span>
        </button>
        <?php require_once __DIR__ . '/chat_sidebar.php'; ?>
    <?php endif; ?>

    <!-- Scripts moved outside footer to prevent grid-item issues -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <?php if (isLoggedIn()): ?>
        <script src="../assets/js/chat.js"></script>
    <?php endif; ?>
</body>
</html>