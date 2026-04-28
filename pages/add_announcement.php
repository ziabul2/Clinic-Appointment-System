<?php
/**
 * Add New Announcement Page
 */
$page_title = "Create Announcement";
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'root'])) {
    redirect('dashboard.php');
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="manage_announcements.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0">Create New Announcement</h2>
            </div>

            <div class="card shadow border-0 overflow-hidden">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-broadcast-tower me-2"></i>Broadcast Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="../process.php?action=save_announcement" method="POST" class="needs-validation" novalidate>
                        <?php echo csrf_input(); ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Announcement Title</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-heading"></i></span>
                                <input type="text" name="title" class="form-control" required placeholder="Enter a catchy subject line">
                            </div>
                            <div class="form-text">Keep it short and informative.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Broadcast To (Target Audience)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-users"></i></span>
                                <select name="target_role" class="form-select" required>
                                    <option value="all">Everyone (All Staff)</option>
                                    <option value="doctor">Doctors Only</option>
                                    <option value="receptionist">Receptionists Only</option>
                                    <option value="admin">Administrators Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Announcement Message</label>
                            <textarea name="message" class="form-control" rows="8" required placeholder="Type the full details of your announcement here..."></textarea>
                            <div class="form-text">You can use multiple lines. This will be shown in the notification and log.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold">
                                <i class="fas fa-paper-plane me-2"></i>Post & Send Notifications
                            </button>
                            <a href="manage_announcements.php" class="btn btn-light btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.input-group-text {
    border: none;
    width: 45px;
    justify-content: center;
}
.form-control, .form-select {
    border: 1px solid #e2e8f0;
    padding: 0.75rem 1rem;
}
.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
