<?php
$page_title = "Notifications";
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect('../pages/login.php');
}

try {
    $uid = $_SESSION['user_id'];
    // show up to 200 recent notifications
    $notifications = getNotifications($db, $uid, 200);
} catch (Exception $e) {
    logAction('NOTIFICATIONS_PAGE_ERROR', $e->getMessage());
    $error = 'Failed to load notifications.';
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-bell"></i> Notifications</h1>
        <div>
            <form method="POST" action="../process.php?action=notifications_mark_read" class="d-inline" id="markAllForm">
                <?php echo csrf_input(); ?>
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $n): if (intval($n['is_read']) === 0) echo '<input type="hidden" name="ids[]" value="' . intval($n['id']) . '">'; endforeach; ?>
                <?php endif; ?>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double"></i> Mark all as read</button>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <?php
            $unread = [];
            $read = [];
            if (!empty($notifications)) {
                foreach ($notifications as $n) {
                    if (intval($n['is_read']) === 0) {
                        $unread[] = $n;
                    } else {
                        $read[] = $n;
                    }
                }
            }
        ?>

        <!-- Unread Notifications Section -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark" style="border-bottom: 2px solid #ffc107;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Unread Notifications</h5>
                        <span class="badge bg-danger rounded-pill"><?php echo count($unread); ?></span>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($unread)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-check-circle fa-2x mb-3" style="opacity: 0.5;"></i>
                            <p>All caught up!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unread as $n): ?>
                            <div class="card border-start border-warning border-3 m-2 shadow-sm" style="background: #fffbf0;">
                                <div class="card-body">
                                    <div class="fw-bold text-dark mb-2" style="font-size: 1rem;">
                                        <?php echo htmlspecialchars($n['title']); ?>
                                    </div>
                                    <div class="small text-muted mb-2" style="line-height: 1.5;">
                                        <?php echo htmlspecialchars($n['message']); ?>
                                    </div>
                                    <div class="small text-muted mb-3" style="display: block;">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars(date('M d, Y \a\t h:i A', strtotime($n['created_at']))); ?>
                                    </div>
                                    <form method="POST" action="../process.php?action=notifications_mark_read" style="display: inline;">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="ids[]" value="<?php echo intval($n['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-check"></i> Mark as read
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Read Notifications Section -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white" style="border-bottom: 2px solid #6c757d;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-archive"></i> Read Notifications</h5>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo count($read); ?></span>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($read)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x mb-3" style="opacity: 0.5;"></i>
                            <p>No archived notifications.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($read as $n): ?>
                            <div class="card border-0 m-2 shadow-sm" style="background: #f8f9fa; opacity: 0.85;">
                                <div class="card-body">
                                    <div class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($n['title']); ?>
                                    </div>
                                    <div class="small text-muted mb-2" style="line-height: 1.5;">
                                        <?php echo htmlspecialchars($n['message']); ?>
                                    </div>
                                    <div class="small text-muted" style="display: block;">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars(date('M d, Y \a\t h:i A', strtotime($n['created_at']))); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php require_once '../includes/footer.php'; ?>
