<?php
/**
 * Session History Page - Aggregated View
 */
$page_title = "User Activity Audit";
require_once __DIR__ . '/../includes/header.php';

// Check permissions
$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'root'])) {
    redirect('dashboard.php');
}

// Fetch Aggregated History
$summary = [];
try {
    $q = $db->prepare("
        SELECT 
            u.user_id, u.username, u.role, u.last_activity,
            d.first_name as dfn, d.last_name as dln,
            COUNT(ul.id) as total_sessions,
            SUM(ul.duration_seconds) as total_duration,
            MAX(ul.login_time) as last_login,
            (SELECT status FROM user_logins WHERE user_id = u.user_id ORDER BY login_time DESC LIMIT 1) as current_status
        FROM users u
        LEFT JOIN user_logins ul ON u.user_id = ul.user_id
        LEFT JOIN doctors d ON u.doctor_id = d.doctor_id
        GROUP BY u.user_id
        ORDER BY last_login DESC
    ");
    $q->execute();
    $summary = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching summary: " . $e->getMessage();
}

function formatDuration($seconds) {
    if (!$seconds) return '0s';
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
            <h2 class="fw-bold mb-0"><i class="fas fa-user-clock text-primary me-2"></i>Staff Activity Audit</h2>
            <p class="text-muted">Consolidated overview of total time spent and system usage.</p>
        </div>
        <a href="admin_tools.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Tools
        </a>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">User Engagement Summary</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Staff Member</th>
                                    <th>Role</th>
                                    <th class="text-center">Sessions</th>
                                    <th>Total Time Spent</th>
                                    <th>Last Activity</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($summary)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            No activity logs found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($summary as $s): 
                                        $displayName = $s['username'];
                                        if ($s['dfn']) $displayName = 'Dr. ' . $s['dfn'] . ' ' . $s['dln'];
                                        
                                        // Improved Online Logic: check user_logins status OR last_activity within 5 mins
                                        $isActiveByActivity = false;
                                        if (!empty($s['last_activity'])) {
                                            $lastAct = strtotime($s['last_activity']);
                                            if (time() - $lastAct < 300) $isActiveByActivity = true;
                                        }
                                        $isOnline = ($s['current_status'] == 'active' || $isActiveByActivity);
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($displayName); ?></div>
                                                        <small class="text-muted">UID: <?php echo $s['user_id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?php echo strtoupper($s['role']); ?></span></td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-info text-white"><?php echo $s['total_sessions']; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 me-2" style="min-width: 100px;">
                                                        <div class="progress" style="height: 6px;">
                                                            <?php 
                                                            // Cap at 10 hours for bar visualization
                                                            $percent = min(100, ($s['total_duration'] / 36000) * 100); 
                                                            ?>
                                                            <div class="progress-bar bg-success" style="width: <?php echo $percent; ?>%;"></div>
                                                        </div>
                                                    </div>
                                                    <span class="fw-bold small"><?php echo formatDuration($s['total_duration']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="fw-bold"><?php echo $s['last_login'] ? date('M d, H:i', strtotime($s['last_login'])) : '-'; ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($isOnline): ?>
                                                    <span class="badge bg-success rounded-pill pulse-badge">
                                                        <i class="fas fa-circle me-1 small"></i> Online
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-pill">Offline</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?php echo $s['user_id']; ?>">
                                                    <i class="fas fa-list-ul"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <!-- Expandable Details Row -->
                                        <tr class="collapse bg-light" id="details-<?php echo $s['user_id']; ?>">
                                            <td colspan="7" class="p-0">
                                                <div class="p-4">
                                                    <h6 class="fw-bold mb-3 small text-uppercase text-primary">Recent Sessions for <?php echo htmlspecialchars($s['username']); ?></h6>
                                                    <div class="table-responsive bg-white rounded shadow-sm border">
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <thead class="border-bottom">
                                                                <tr class="small text-muted">
                                                                    <th class="ps-3">Login</th>
                                                                    <th>Logout</th>
                                                                    <th>Duration</th>
                                                                    <th>IP Address</th>
                                                                    <th class="pe-3">Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $details = $db->prepare("SELECT * FROM user_logins WHERE user_id = :uid ORDER BY login_time DESC LIMIT 10");
                                                                $details->execute(['uid' => $s['user_id']]);
                                                                while ($dl = $details->fetch(PDO::FETCH_ASSOC)):
                                                                ?>
                                                                    <tr class="small">
                                                                        <td class="ps-3"><?php echo date('M d, H:i:s', strtotime($dl['login_time'])); ?></td>
                                                                        <td><?php echo $dl['logout_time'] ? date('M d, H:i:s', strtotime($dl['logout_time'])) : '-'; ?></td>
                                                                        <td><?php echo formatDuration($dl['duration_seconds']); ?></td>
                                                                        <td><code><?php echo $dl['ip_address']; ?></code></td>
                                                                        <td class="pe-3">
                                                                            <span class="badge bg-light text-dark"><?php echo $dl['status']; ?></span>
                                                                        </td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
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
    </div>
</div>

<style>
.pulse-badge {
    animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
    100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
