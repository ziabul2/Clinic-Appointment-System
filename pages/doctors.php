<?php
$page_title = "Doctors Management";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Handle deletion
    if (isset($_GET['delete_id'])) {
        $doctor_id = sanitizeInput($_GET['delete_id']);
        $query = "DELETE FROM doctors WHERE doctor_id = :doctor_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        if ($stmt->execute()) {
            logAction("DOCTOR_DELETED", "Doctor ID: $doctor_id deleted");
            $_SESSION['success'] = "Doctor deleted successfully!";
        } else {
            throw new Exception("Failed to delete doctor");
        }
        redirect('doctors.php');
    }

    // Search and pagination
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $where_clause = '';
    $params = [];
    if (!empty($search)) {
        $where_clause = "WHERE first_name LIKE :search OR last_name LIKE :search OR specialization LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }

    $count_query = "SELECT COUNT(*) as total FROM doctors $where_clause";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $k => $v) $count_stmt->bindValue($k, $v);
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $total_pages = ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;

    $query = "SELECT d.*, (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.doctor_id) as total_appointments 
              FROM doctors d $where_clause ORDER BY d.created_at DESC LIMIT :offset, :per_page";
    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction("DOCTORS_PAGE_ERROR", "Database error: " . $e->getMessage());
    $error = "Failed to load doctors data.";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-md"></i> Doctors Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_doctor.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Doctor
        </a>
    </div>
</div>

<div class="list-container mb-4">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-search"></i> Search Doctors</h5>
        <span class="text-muted small">Total: <?php echo $total; ?> doctors</span>
    </div>
    <div class="list-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-12">
                <div class="input-group">
                    <input type="text" class="form-control realtime-search" name="search" data-type="doctors" placeholder="Search doctors by ID, name, email, or specialization..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="doctors.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="list-container">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-list"></i> Doctors List</h5>
    </div>
    <div class="list-body">
        <?php if (empty($doctors)): ?>
            <div class="text-center py-5">
                <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                <h5>No doctors found</h5>
                <p class="text-muted">Add doctors to manage schedules and appointments.</p>
                <a href="add_doctor.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Doctor</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:5%;">ID</th>
                            <th style="white-space: nowrap;">Doctor</th>
                            <th style="width:15%;">Specialization</th>
                            <th style="width:20%;">Email & Phone</th>
                            <th style="width:25%;">Availability</th>
                            <th style="width:10%;">Appts</th>
                            <th class="text-end" style="min-width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="doctorsTableBody">
                        <?php foreach ($doctors as $doc): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?php echo $doc['doctor_id']; ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($doc['profile_picture']) && file_exists(__DIR__ . '/../uploads/doctors/' . $doc['profile_picture'])): ?>
                                            <img src="../uploads/doctors/<?php echo htmlspecialchars($doc['profile_picture']); ?>" alt="Doctor" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:40px;height:40px;font-size:18px;"><i class="fas fa-user-md"></i></div>
                                        <?php endif; ?>
                                        <strong style="white-space: nowrap;">Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($doc['specialization']); ?></td>
                                <td class="text-break">
                                    <small>
                                        <div class="text-break"><?php echo htmlspecialchars($doc['email']); ?></div>
                                        <div class="text-muted"><?php echo htmlspecialchars($doc['phone']); ?></div>
                                    </small>
                                </td>
                                <td class="text-break">
                                    <small>
                                        <div><strong><?php echo htmlspecialchars($doc['available_days']); ?></strong></div>
                                        <div class="text-muted"><?php echo date('H:i', strtotime($doc['available_time_start'])) . ' - ' . date('H:i', strtotime($doc['available_time_end'])); ?></div>
                                    </small>
                                </td>
                                <td><span class="badge bg-info"><?php echo $doc['total_appointments']; ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                                        <a href="view_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="edit_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="doctors.php?delete_id=<?php echo $doc['doctor_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>

                                    <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                                    <div class="actions-collapse d-md-none">
                                        <a href="view_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-1"></i> View</a>
                                        <a href="edit_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-outline-warning w-100 mb-1"><i class="fas fa-edit me-1"></i> Edit</a>
                                        <a href="doctors.php?delete_id=<?php echo $doc['doctor_id']; ?>" class="btn btn-outline-danger w-100" onclick="return false;"><i class="fas fa-trash me-1"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Doctors pagination" id="paginationContainer">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>