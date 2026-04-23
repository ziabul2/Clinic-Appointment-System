<?php
$page_title = 'Employees';
require_once '../includes/header.php';
if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) redirect('../index.php');

$q = $db->prepare('SELECT user_id, username, role, email, first_name, last_name, profile_picture FROM users ORDER BY role, username');
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users"></i> Employees</h1>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="alert alert-info">No users found.</div>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Avatar</th>
                <th>Username</th>
                <th>Name</th>
                <th>Role</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <?php if (!empty($r['profile_picture']) && file_exists(__DIR__ . '/../uploads/users/' . $r['profile_picture'])): ?>
                            <img src="../uploads/users/<?php echo htmlspecialchars($r['profile_picture']); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($r['username']); ?></td>
                    <td><?php echo htmlspecialchars(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))); ?></td>
                    <td><?php echo htmlspecialchars($r['role']); ?></td>
                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                            <a class="btn btn-sm btn-primary" href="edit_user.php?id=<?php echo $r['user_id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                            <a class="btn btn-sm btn-danger" href="../process.php?action=delete_user&id=<?php echo $r['user_id']; ?>" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>

                        <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                        <div class="actions-collapse d-md-none">
                            <a class="btn btn-primary w-100 mb-1" href="edit_user.php?id=<?php echo $r['user_id']; ?>"><i class="fas fa-edit me-1"></i> Edit</a>
                            <a class="btn btn-danger w-100" href="../process.php?action=delete_user&id=<?php echo $r['user_id']; ?>"><i class="fas fa-trash me-1"></i> Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
