<?php
$page_title = "Add User";
require_once '../includes/header.php';

// Only Admins may access
checkRole(['Admin']);

try {
    $doc_stmt = $db->prepare("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY first_name");
    $doc_stmt->execute();
    $doctors = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $doctors = [];
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-plus"></i> Add User</h1>
    <div>
        <a href="users.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="../process.php?action=add_user" data-ajax="true">
            <?php echo csrf_input(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input class="form-control" name="username" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" name="email">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="Receptionist">Receptionist</option>
                        <option value="Doctor">Doctor</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control" name="password" type="password" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Associate Doctor (optional)</label>
                <select class="form-select" name="doctor_id">
                    <option value="">None</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo $d['doctor_id']; ?>"><?php echo htmlspecialchars($d['first_name'].' '.$d['last_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">If creating a Doctor user, you can link to an existing doctor profile.</small>
            </div>

            <div class="d-flex justify-content-end">
                <a href="users.php" class="btn btn-secondary me-2">Cancel</a>
                <button class="btn btn-primary" type="submit">Create User</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
