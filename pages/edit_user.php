<?php
$page_title = "Edit User";
require_once '../includes/header.php';

// Only Admins may access
if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) redirect('../index.php');

$user_id = $_GET['id'] ?? null;
if (!$user_id) redirect('employees.php');

try {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'User not found.';
        redirect('employees.php');
    }

    $doc_stmt = $db->prepare("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY first_name");
    $doc_stmt->execute();
    $doctors = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    redirect('employees.php');
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-edit"></i> Edit User: <?php echo htmlspecialchars($user['username']); ?></h1>
    <div>
        <a href="employees.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="../process.php?action=update_user">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name</label>
                    <input class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name</label>
                    <input class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role" required>
                        <option value="Admin" <?php echo ($user['role'] == 'admin' || $user['role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="Receptionist" <?php echo ($user['role'] == 'receptionist' || $user['role'] == 'Receptionist') ? 'selected' : ''; ?>>Receptionist</option>
                        <option value="Doctor" <?php echo ($user['role'] == 'doctor' || $user['role'] == 'Doctor') ? 'selected' : ''; ?>>Doctor</option>
                        <option value="Patient" <?php echo ($user['role'] == 'patient' || $user['role'] == 'Patient') ? 'selected' : ''; ?>>Patient</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Associate Doctor (optional)</label>
                    <select class="form-select" name="doctor_id">
                        <option value="">None</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['doctor_id']; ?>" <?php echo ($user['doctor_id'] == $d['doctor_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['first_name'].' '.$d['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> To change password, use the "Reset Password" tool in User Management.
            </div>

            <div class="d-flex justify-content-end">
                <a href="employees.php" class="btn btn-secondary me-2">Cancel</a>
                <button class="btn btn-primary" type="submit">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
