<?php
$page_title = 'Employees';
require_once '../includes/header.php';
if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) redirect('../index.php');

$q = $db->prepare('SELECT u.user_id, u.username, u.role, u.email, u.first_name, u.last_name, u.profile_picture, d.first_name as doc_first, d.last_name as doc_last, d.profile_picture as doc_pic 
                   FROM users u 
                   LEFT JOIN doctors d ON u.doctor_id = d.doctor_id 
                   ORDER BY u.role, u.username');
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
                        <?php 
                        $pic = !empty($r['profile_picture']) ? $r['profile_picture'] : ($r['doc_pic'] ?? '');
                        if (!empty($pic) && file_exists(__DIR__ . '/../uploads/users/' . $pic)): ?>
                            <img src="../uploads/users/<?php echo htmlspecialchars($pic); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                        <?php elseif (!empty($pic) && file_exists(__DIR__ . '/../uploads/doctors/' . $pic)): ?>
                            <img src="../uploads/doctors/<?php echo htmlspecialchars($pic); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($r['username']); ?></td>
                    <td>
                        <?php 
                        $fname = !empty($r['first_name']) ? $r['first_name'] : ($r['doc_first'] ?? '');
                        $lname = !empty($r['last_name']) ? $r['last_name'] : ($r['doc_last'] ?? '');
                        $fullName = trim($fname . ' ' . $lname);
                        echo htmlspecialchars(!empty($fullName) ? $fullName : $r['username']); 
                        ?>
                    </td>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars($r['role']); ?></span></td>
                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item text-primary" href="edit_user.php?id=<?php echo $r['user_id']; ?>"><i class="fas fa-edit me-2"></i> Edit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../process.php?action=delete_user&id=<?php echo $r['user_id']; ?>" onclick="return confirm('Delete this user?');"><i class="fas fa-trash me-2"></i> Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
    </div>
</div>

<script>
// No specific JS needed for simple links, but kept for consistency
</script>

<?php require_once '../includes/footer.php'; ?>
