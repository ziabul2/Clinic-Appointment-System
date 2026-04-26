<?php
$page_title = 'Employees';
require_once '../includes/header.php';

if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) {
    redirect('../index.php');
}

$q = $db->prepare('SELECT u.user_id, u.username, u.role, u.email, u.first_name, u.last_name, u.profile_picture, 
                   d.first_name as doc_first, d.last_name as doc_last, d.profile_picture as doc_pic 
                   FROM users u 
                   LEFT JOIN doctors d ON u.doctor_id = d.doctor_id 
                   ORDER BY u.role, u.username');
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="list-container">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i> Employees & Staff</h5>
        <div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
    
    <div class="list-body">
        <?php if (empty($rows)): ?>
            <div class="alert alert-info">No users found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:10%;">Avatar</th>
                            <th style="width:20%;">User Details</th>
                            <th style="width:15%;">Role</th>
                            <th style="width:25%;">Contact</th>
                            <th style="width:20%;">Doctor Link</th>
                            <th style="width:10%; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php 
                                        $pic = !empty($r['profile_picture']) ? $r['profile_picture'] : ($r['doc_pic'] ?? '');
                                        if (!empty($pic) && file_exists(__DIR__ . '/../uploads/users/' . $pic)): ?>
                                            <img src="../uploads/users/<?php echo htmlspecialchars($pic); ?>" class="rounded-circle shadow-sm" style="width:45px;height:45px;object-fit:cover; border: 2px solid rgba(255,255,255,0.5);">
                                        <?php elseif (!empty($pic) && file_exists(__DIR__ . '/../uploads/doctors/' . $pic)): ?>
                                            <img src="../uploads/doctors/<?php echo htmlspecialchars($pic); ?>" class="rounded-circle shadow-sm" style="width:45px;height:45px;object-fit:cover; border: 2px solid rgba(255,255,255,0.5);">
                                        <?php else: ?>
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px; color: #ced4da;">
                                                <i class="fas fa-user fa-lg"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['username']); ?></div>
                                    <div class="small text-muted">
                                        <?php 
                                        $fname = !empty($r['first_name']) ? $r['first_name'] : ($r['doc_first'] ?? '');
                                        $lname = !empty($r['last_name']) ? $r['last_name'] : ($r['doc_last'] ?? '');
                                        echo htmlspecialchars(trim($fname . ' ' . $lname) ?: 'No Name Set'); 
                                        ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-info-soft text-info"><?php echo ucfirst(htmlspecialchars($r['role'])); ?></span></td>
                                <td><small class="text-muted"><i class="far fa-envelope me-1"></i> <?php echo htmlspecialchars($r['email']); ?></small></td>
                                <td>
                                    <?php if ($r['doc_first']): ?>
                                        <span class="badge bg-primary-soft text-primary">
                                            <i class="fas fa-user-md me-1"></i> <?php echo htmlspecialchars('Dr. ' . $r['doc_first'] . ' ' . $r['doc_last']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not Linked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <!-- Desktop Actions -->
                                    <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                                        <a href="edit_user.php?id=<?php echo $r['user_id']; ?>" class="btn btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="../process.php?action=delete_user&id=<?php echo $r['user_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete employee?');" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>

                                    <!-- Mobile Actions -->
                                    <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                                    <div class="actions-collapse d-md-none">
                                        <div class="collapse-details mb-2">
                                            <strong><?php echo htmlspecialchars($r['username']); ?></strong>
                                        </div>
                                        <a href="edit_user.php?id=<?php echo $r['user_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-edit me-2"></i> Edit Employee</a>
                                        <a href="../process.php?action=delete_user&id=<?php echo $r['user_id']; ?>" class="btn btn-outline-danger w-100" onclick="return confirm('Delete employee?');"><i class="fas fa-trash me-2"></i> Delete</a>
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
<?php require_once '../includes/footer.php'; ?>
