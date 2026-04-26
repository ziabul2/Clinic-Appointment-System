<?php
$page_title = "Old Patients Archive";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Search functionality
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $where_clause = '';
    $params = [];

    if (!empty($search)) {
        $where_clause = "WHERE (p.first_name LIKE :search OR p.last_name LIKE :search OR p.email LIKE :search OR p.phone LIKE :search) AND DATE(p.admitted_at) < CURDATE()";
        $params[':search'] = "%$search%";
    } else {
        $where_clause = "WHERE DATE(p.admitted_at) < CURDATE()";
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
    logAction("OLD_PATIENTS_PAGE_ERROR", "Database error: " . $e->getMessage());
    $error = "Failed to load archived patients.";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-archive"></i> Old Patients Archive</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="patients.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Today's Patients
        </a>
    </div>
</div>

<!-- Search Section -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search archive..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <span class="text-muted">Total Archived: <?php echo $total_patients; ?></span>
            </div>
        </form>
    </div>
</div>

<!-- Patients Table -->
<div class="card shadow">
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-4">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5>No archived patients found</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Admission Date</th>
                            <th>Contact</th>
                            <th>Appointments</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>#<?php echo $patient['patient_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong></td>
                                <td>
                                    <small class="text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('M j, Y', strtotime($patient['admitted_at'])); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><span class="badge bg-info"><?php echo $patient['total_appointments']; ?></span></td>
                                <td><?php echo $patient['last_visit'] ? date('M j, Y', strtotime($patient['last_visit'])) : '-'; ?></td>
                                <td>
                                    <!-- Desktop Actions -->
                                    <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                                        <a href="../process.php?action=readmit_patient&id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-success" title="Readmit"><i class="fas fa-user-check"></i></a>
                                        <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="add_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-info" title="New Appointment"><i class="fas fa-calendar-plus"></i></a>
                                    </div>

                                    <!-- Mobile Actions -->
                                    <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                                    <div class="actions-collapse d-md-none">
                                        <div class="collapse-details mb-2">
                                            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                                        </div>
                                        <a href="../process.php?action=readmit_patient&id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-success w-100 mb-1"><i class="fas fa-user-check me-2"></i> Readmit</a>
                                        <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-2"></i> View Profile</a>
                                        <a href="add_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-info w-100" title="New Appointment"><i class="fas fa-calendar-plus me-2"></i> New Appointment</a>
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
