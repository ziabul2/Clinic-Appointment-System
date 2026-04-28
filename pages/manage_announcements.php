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
        <a href="add_announcement.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Announcement
        </a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
