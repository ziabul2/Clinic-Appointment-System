<?php
/**
 * Session History Page
 * Accessible only by Admin/Root
 */
$page_title = "Session History";
require_once __DIR__ . '/../includes/header.php';

// Check permissions
$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'root'])) {
    redirect('dashboard.php');
}

// Fetch history
$history = [];
try {
    $q = $db->prepare("
        SELECT ul.*, u.username, u.role, d.first_name as dfn, d.last_name as dln 
        FROM user_logins ul
        JOIN users u ON ul.user_id = u.user_id
        LEFT JOIN doctors d ON u.doctor_id = d.doctor_id
        ORDER BY ul.login_time DESC
        LIMIT 100
    ");
    $q->execute();
    $history = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching history: " . $e->getMessage();
}

function formatDuration($seconds) {
    if (!$seconds) return '-';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    $parts = [];
    if ($h > 0) $parts[] = $h . 'h';
    if ($m > 0) $parts[] = $m . 'm';
    if ($s > 0 || empty($parts)) $parts[] = $s . 's';
    return implode(' ', $parts);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>User Session History</h2>
            <p class="text-muted">Track who logged in, when, and for how long.</p>
        </div>
        <a href="admin_tools.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Tools
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Role</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                            <th>IP Address</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                                    No session history found yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $h): 
                                $displayName = $h['username'];
                                if ($h['dfn']) $displayName = 'Dr. ' . $h['dfn'] . ' ' . $h['dln'];
                                
                                $statusClass = 'bg-secondary';
                                if ($h['status'] == 'active') $statusClass = 'bg-success';
                                if ($h['status'] == 'logged_out') $statusClass = 'bg-primary';
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;">
                                                <i class="fas fa-user text-muted small"></i>
                                            </div>
                                            <strong><?php echo htmlspecialchars($displayName); ?></strong>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo strtoupper($h['role']); ?></span></td>
                                    <td><small class="fw-bold"><?php echo date('M d, H:i', strtotime($h['login_time'])); ?></small></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $h['logout_time'] ? date('M d, H:i', strtotime($h['logout_time'])) : 'Still active'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary fw-bold">
                                            <?php echo formatDuration($h['duration_seconds']); ?>
                                        </span>
                                    </td>
                                    <td><code class="small"><?php echo htmlspecialchars($h['ip_address']); ?></code></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $statusClass; ?> rounded-pill">
                                            <?php echo ucfirst($h['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
