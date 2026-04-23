<?php
$page_title = 'Map Patients to Users';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');
// Only admin or receptionist should access
checkRole(['admin','receptionist']);

// Check if users table already has patient_id
$hasPatientColumn = false;
try {
    $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'patient_id'");
    $colChk->execute();
    $r = $colChk->fetch(PDO::FETCH_ASSOC);
    $hasPatientColumn = intval($r['cnt'] ?? 0) > 0;
} catch (Exception $e) { $hasPatientColumn = false; }

// Fetch patients and users
$patients = [];
$users = [];
try {
    $pq = $db->query('SELECT patient_id, first_name, last_name, email FROM patients ORDER BY first_name, last_name');
    $patients = $pq ? $pq->fetchAll(PDO::FETCH_ASSOC) : [];
    $uq = $db->query('SELECT user_id, username, email, role, patient_id FROM users ORDER BY username');
    $users = $uq ? $uq->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    logAction('MAP_PAGE_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Failed to load users or patients.';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="fas fa-link"></i> Map Patients to Users</h1>
    <div>
        <?php if (!$hasPatientColumn): ?>
            <form method="POST" action="../process.php?action=apply_patient_column" style="display:inline">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="confirm" value="1">
                <button class="btn btn-sm btn-warning">Add users.patient_id column</button>
            </form>
        <?php else: ?>
            <span class="badge bg-success">Schema ready</span>
        <?php endif; ?>
    </div>
</div>

<!-- Flash messages handled centrally in header as floating notifications -->

<div class="card">
    <div class="card-body">
        <p class="small text-muted">Use this tool to link a patient record to an application user account. This allows in-app notifications to reach patient users.</p>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Phone / Email</th>
                    <th>Linked User</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['email'] ?? '') . '<br>' . htmlspecialchars($p['phone'] ?? ''); ?></td>
                        <td>
                            <form method="POST" action="../process.php?action=link_patient_user" class="d-flex">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="patient_id" value="<?php echo intval($p['patient_id']); ?>">
                                <select name="user_id" class="form-select form-select-sm">
                                    <option value="">-- Unlink / None --</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo intval($u['user_id']); ?>" <?php echo (isset($u['patient_id']) && intval($u['patient_id']) === intval($p['patient_id'])) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['username'] . ' (' . $u['role'] . ')'); ?> <?php echo $u['email'] ? ' - ' . htmlspecialchars($u['email']) : ''; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary ms-2">Save</button>
                            </form>
                        </td>
                        <td class="text-end small text-muted">ID: <?php echo intval($p['patient_id']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
