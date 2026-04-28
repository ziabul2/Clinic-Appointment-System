<?php
$page_title = "Patients Management";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Handle patient deletion
    if (isset($_GET['delete_id'])) {
        $patient_id = sanitizeInput($_GET['delete_id']);
        
        $query = "DELETE FROM patients WHERE patient_id = :patient_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':patient_id', $patient_id);
        
        if ($stmt->execute()) {
            logAction("PATIENT_DELETED", "Patient ID: $patient_id deleted");
            $_SESSION['success'] = "Patient deleted successfully!";
        } else {
            throw new Exception("Failed to delete patient");
        }
        redirect('patients.php');
    }

    // Search functionality
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $where_clause = '';
    $params = [];

    if (!empty($search)) {
        $where_clause = "WHERE (p.first_name LIKE :search OR p.last_name LIKE :search OR p.email LIKE :search OR p.phone LIKE :search) AND DATE(p.admitted_at) = CURDATE()";
        $params[':search'] = "%$search%";
    } else {
        $where_clause = "WHERE DATE(p.admitted_at) = CURDATE()";
    }

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM patients p $where_clause";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_patients = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $total_pages = ceil($total_patients / $per_page);
    $offset = ($page - 1) * $per_page;

    // Get patients with pagination
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.patient_id) as total_appointments,
                     (SELECT MAX(appointment_date) FROM appointments a WHERE a.patient_id = p.patient_id) as last_visit
              FROM patients p 
              $where_clause 
              ORDER BY p.admitted_at DESC 
              LIMIT :offset, :per_page";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction("PATIENTS_PAGE_ERROR", "Database error: " . $e->getMessage());
    $error = "Failed to load patients data.";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-injured"></i> Patients Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="old_patients.php" class="btn btn-outline-secondary">
            <i class="fas fa-archive"></i> Old Patients Archive
        </a>
        <a href="add_patient.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Patient
        </a>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control realtime-search" name="search" data-type="patients" placeholder="Search patients by ID, name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <?php if (!empty($search)): ?>
                    <a href="patients.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                <?php endif; ?>
                <span class="text-muted ms-2">Total: <?php echo $total_patients; ?> patients</span>
            </div>
        </form>
    </div>
</div>

<!-- Patients Table -->
<div class="card shadow">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0"><i class="fas fa-list"></i> Patients List</h5>
    </div>
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-4">
                <i class="fas fa-user-injured fa-3x text-muted mb-3"></i>
                <h5>No patients found</h5>
                <p class="text-muted">
                    <?php echo !empty($search) ? 'Try adjusting your search terms.' : 'Get started by adding your first patient.'; ?>
                </p>
                <?php if (empty($search)): ?>
                    <a href="add_patient.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Patient
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                             <th style="width:5%;">ID</th>
                             <th style="white-space: nowrap;">Patient Name</th>
                             <th style="width:10%;">Time</th>
                             <th style="width:20%;">Email & Phone</th>
                             <th style="width:10%;">Date of Birth</th>
                             <th style="width:10%;">Emergency</th>
                             <th style="width:5%;">Appts</th>
                             <th style="width:10%;">Last Visit</th>
                             <th class="text-end" style="min-width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="patientsTableBody">
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?php echo $patient['patient_id']; ?></span></td>
                                 <td>
                                     <strong style="white-space: nowrap;"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                                     <?php if ($patient['gender']): ?>
                                         <br><small class="text-muted"><?php echo $patient['gender']; ?></small>
                                     <?php endif; ?>
                                 </td>
                                 <td>
                                     <span class="badge bg-light text-dark border">
                                         <i class="far fa-clock me-1"></i>
                                         <?php echo date('g:i A', strtotime($patient['admitted_at'])); ?>
                                     </span>
                                 </td>
                                <td class="text-break">
                                    <small>
                                        <?php if ($patient['email']): ?>
                                            <div><?php echo htmlspecialchars($patient['email']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($patient['phone']): ?>
                                            <div class="text-muted"><?php echo htmlspecialchars($patient['phone']); ?></div>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php if ($patient['date_of_birth']): ?>
                                            <?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php if ($patient['emergency_contact']): ?>
                                            <?php echo htmlspecialchars($patient['emergency_contact']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-info"><?php echo $patient['total_appointments']; ?></span></td>
                                <td>
                                    <small>
                                        <?php if ($patient['last_visit']): ?>
                                            <?php echo date('M j, Y', strtotime($patient['last_visit'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <!-- Desktop Actions -->
                                    <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                                        <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="consultation_history.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary" title="Consultation History"><i class="fas fa-notes-medical"></i></a>
                                        <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="add_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-info" title="New Appointment"><i class="fas fa-calendar-plus"></i></a>
                                        <a href="../process.php?action=archive_patient&id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-secondary" onclick="return confirm('Archive patient?');" title="Archive"><i class="fas fa-archive"></i></a>
                                        <a href="patients.php?delete_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete patient?');" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>

                                    <!-- Mobile Actions -->
                                    <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                                    <div class="actions-collapse d-md-none">
                                        <div class="collapse-details mb-2">
                                            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                                            <div class="text-muted small"><?php echo htmlspecialchars($patient['phone'] ?? 'No phone'); ?></div>
                                        </div>
                                        <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-2"></i> View Profile</a>
                                        <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-warning w-100 mb-1"><i class="fas fa-edit me-2"></i> Edit Patient</a>
                                        <a href="add_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-info w-100 mb-1"><i class="fas fa-calendar-plus me-2"></i> New Appointment</a>
                                        <a href="../process.php?action=archive_patient&id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-secondary w-100 mb-1" onclick="return confirm('Archive patient?');"><i class="fas fa-archive me-2"></i> Archive</a>
                                        <a href="patients.php?delete_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-danger w-100" onclick="return confirm('Delete patient?');"><i class="fas fa-trash me-2"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Patients pagination" id="paginationContainer">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>