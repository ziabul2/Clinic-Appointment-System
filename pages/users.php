<?php
$page_title = "User Management";
require_once '../includes/header.php';

// Only Admins may access this page (case-insensitive)
checkRole(['Admin']);

try {
    $query = "SELECT u.*, d.first_name as doctor_first, d.last_name as doctor_last FROM users u LEFT JOIN doctors d ON u.doctor_id = d.doctor_id ORDER BY u.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch doctors for optional linking
    $doc_stmt = $db->prepare("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY first_name");
    $doc_stmt->execute();
    $doctors = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction('USERS_PAGE_ERROR', $e->getMessage());
    $error = 'Failed to load users.';
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users"></i> User Management</h1>
    <div>
        <a href="add_user.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importUsersModal"><i class="fas fa-file-import"></i> Import Users</button>
    </div>
</div>

<!-- Import Users Modal -->
<div class="modal fade" id="importUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="../process.php?action=import_users" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <div class="modal-header"><h5 class="modal-title">Import Users (CSV)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Upload a CSV file with columns: <strong>username,email,role,doctor_id</strong> (header row optional). Existing usernames will be updated.</p>
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="csv_file" accept="text/csv" class="form-control" required>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="replace_existing" id="replace_existing">
                        <label class="form-check-label" for="replace_existing">Replace existing users' data when username matches</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Import</button></div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<!-- Users List -->
<div class="list-container">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-list"></i> Users List</h5>
    </div>
    <div class="list-body">
        <?php if (empty($users)): ?>
            <div class="p-3 text-muted">No users found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:8%;">ID</th>
                            <th style="width:18%;">Username</th>
                            <th style="width:22%;">Email</th>
                            <th style="width:12%;">Role</th>
                            <th style="width:20%;">Linked Doctor</th>
                            <th style="width:15%;">Created</th>
                            <th style="width:5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?php echo $u['user_id']; ?></span></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><small><?php echo htmlspecialchars($u['email']); ?></small></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                <td><small><?php echo $u['doctor_first'] ? htmlspecialchars('Dr. ' . $u['doctor_first'] . ' ' . $u['doctor_last']) : '<span class="text-muted">-</span>'; ?></small></td>
                                <td><small><?php echo date('M j, Y', strtotime($u['created_at'])); ?></small></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <a class="dropdown-item text-primary" href="edit_user.php?id=<?php echo $u['user_id']; ?>">
                                                    <i class="fas fa-edit me-2"></i> Edit User
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#resetModal" data-userid="<?php echo $u['user_id']; ?>" data-username="<?php echo htmlspecialchars($u['username']); ?>">
                                                    <i class="fas fa-key me-2"></i> Reset Password
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="../process.php?action=delete_user&id=<?php echo $u['user_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">
                                                    <i class="fas fa-trash me-2"></i> Delete User
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?php endif; ?>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 15px;">
            <form method="POST" action="../process.php?action=reset_password">
                <?php echo csrf_input(); ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <p class="text-muted">You are about to reset the password for: <br><strong id="reset_username" class="text-dark"></strong></p>
                    <div class="form-floating mb-3">
                        <input class="form-control" type="password" name="new_password" id="new_password" placeholder="New Password" required style="background: #f8f9fa; border-radius: 10px;">
                        <label for="new_password">Enter New Password</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary px-4 btn-login" type="submit">Reset Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Users Modal -->
<div class="modal fade" id="importUsersModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 15px;">
            <form method="POST" action="../process.php?action=import_users" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import Users (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-muted small mb-4">Upload a CSV file with columns: <strong>username, email, role, doctor_id</strong>. Existing usernames will be updated if selected.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">CSV File</label>
                        <input type="file" name="csv_file" accept="text/csv" class="form-control" required style="border-radius: 10px;">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="replace_existing" id="replace_existing">
                        <label class="form-check-label small" for="replace_existing">Update existing users if username matches</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success px-4" type="submit" style="border-radius: 10px;">Start Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var rModal = document.getElementById('resetModal');
    if (rModal) {
        rModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userid = button.getAttribute('data-userid');
            var username = button.getAttribute('data-username');
            document.getElementById('reset_user_id').value = userid;
            document.getElementById('reset_username').textContent = username;
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
