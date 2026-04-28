<?php
/**
 * Announcement Management Page
 */
$page_title = "Manage Announcements";
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'root'])) {
    redirect('dashboard.php');
}

$announcements = $db->query("SELECT a.*, u.username FROM announcements a LEFT JOIN users u ON a.created_by = u.user_id ORDER BY a.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-bullhorn text-primary me-2"></i>System Announcements</h2>
            <p class="text-muted">Broadcast important updates to Doctors and Receptionists.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnModal">
            <i class="fas fa-plus me-2"></i>New Announcement
        </button>
    </div>

    <div class="row">
        <?php if (empty($announcements)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">No announcements posted yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $a): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary rounded-pill"><?php echo strtoupper($a['target_role']); ?></span>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($a['created_at'])); ?></small>
                            </div>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($a['title']); ?></h5>
                            <p class="text-muted small"><?php echo nl2br(htmlspecialchars($a['message'])); ?></p>
                        </div>
                        <div class="card-footer bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">By: <strong><?php echo htmlspecialchars($a['username']); ?></strong></small>
                                <a href="../process.php?action=delete_announcement&id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this announcement?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="../process.php?action=save_announcement" method="POST">
                <?php echo csrf_input(); ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Create Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="Announcement Subject">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Broadcast To</label>
                        <select name="target_role" class="form-select">
                            <option value="all">All Staff</option>
                            <option value="doctor">Doctors Only</option>
                            <option value="receptionist">Receptionists Only</option>
                            <option value="admin">Admins Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message</label>
                        <textarea name="message" class="form-control" rows="4" required placeholder="Type your announcement here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post & Notify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
