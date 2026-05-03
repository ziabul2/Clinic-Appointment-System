<?php
$page_title = "Doctor Dashboard";
require_once '../includes/header.php';

// Check authentication and role
if (!isLoggedIn() || $_SESSION['role'] !== 'Doctor') {
    $_SESSION['error'] = 'Access denied. Doctor login required.';
    redirect('../index.php');
}

// Get doctor_id from user
$user_id = $_SESSION['user_id'];
try {
    $q = $db->prepare("SELECT doctor_id FROM users WHERE user_id = :uid");
    $q->bindParam(':uid', $user_id);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $row['doctor_id'] ?? null;
    if (!$doctor_id) {
        $_SESSION['error'] = 'No doctor profile linked to your user account. Ask admin to link you to a doctor profile.';
        redirect('dashboard.php');
    }
} catch (PDOException $e) {
    logAction('DOCTOR_DASHBOARD_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Failed to load doctor profile.';
    redirect('dashboard.php');
}

$today = date('Y-m-d');

try {
    // Today's appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :doc AND appointment_date = :date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $total_appointments_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Patients admitted today
    $query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :doc AND appointment_date = :date AND is_admitted = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $admitted_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Completed appointments today (assuming 'completed' status means done)
    $query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :doc AND appointment_date = :date AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $completed_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Serial numbers running - let's assume appointments with serial assigned
    $query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :doc AND appointment_date = :date AND appointment_serial IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $serials_running = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Patients needing prescriptions - appointments with prescription field not empty
    $query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :doc AND appointment_date = :date AND prescription IS NOT NULL AND prescription != ''";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $need_prescription = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Next appointment
    $query = "SELECT a.*, p.first_name, p.last_name FROM appointments a 
              LEFT JOIN patients p ON a.patient_id = p.patient_id 
              WHERE a.doctor_id = :doc AND a.appointment_date = :date AND a.status = 'scheduled' 
              ORDER BY a.appointment_time ASC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $next_appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Today's patients list
    $query = "SELECT a.*, p.first_name, p.last_name, p.patient_id, p.email, p.phone FROM appointments a 
              LEFT JOIN patients p ON a.patient_id = p.patient_id 
              WHERE a.doctor_id = :doc AND a.appointment_date = :date 
              ORDER BY a.appointment_time ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $today_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction('DOCTOR_DASHBOARD_STATS_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Failed to load dashboard statistics.';
    $total_appointments_today = $admitted_today = $completed_today = $serials_running = $need_prescription = 0;
    $next_appointment = null;
    $today_patients = [];
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-md"></i> Doctor Dashboard</h1>
    <div>
        <a href="my_appointments.php" class="btn btn-secondary me-2">My Appointments</a>
        <a href="dashboard.php" class="btn btn-outline-primary">Main Dashboard</a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h5 class="card-title">Today's Appointments</h5>
                <h2 class="mb-0"><?php echo $total_appointments_today; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-2x mb-2"></i>
                <h5 class="card-title">Admitted Today</h5>
                <h2 class="mb-0"><?php echo $admitted_today; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-list-ol fa-2x mb-2"></i>
                <h5 class="card-title">Serials Running</h5>
                <h2 class="mb-0"><?php echo $serials_running; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-prescription fa-2x mb-2"></i>
                <h5 class="card-title">Need Prescription</h5>
                <h2 class="mb-0"><?php echo $need_prescription; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Today's Progress</h5>
            </div>
            <div class="card-body">
                <?php
                $progress = $total_appointments_today > 0 ? round(($completed_today / $total_appointments_today) * 100) : 0;
                ?>
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" 
                         aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                        <strong><?php echo $progress; ?>% Complete</strong>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-md-6">
                        <div class="border-end">
                            <h4 class="text-success mb-1"><?php echo $completed_today; ?></h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4 class="text-primary mb-1"><?php echo $total_appointments_today; ?></h4>
                        <small class="text-muted">Total Appointments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Next Appointment -->
<?php if ($next_appointment): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-clock"></i> Next Patient</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><?php echo htmlspecialchars($next_appointment['first_name'] . ' ' . $next_appointment['last_name']); ?></h6>
                        <p class="mb-1"><strong>Time:</strong> <?php echo htmlspecialchars($next_appointment['appointment_time']); ?></p>
                        <p class="mb-1"><strong>Serial:</strong> <?php echo htmlspecialchars($next_appointment['appointment_serial'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="view_patient.php?id=<?php echo $next_appointment['patient_id']; ?>" class="btn btn-primary">View Details</a>
                        <a href="appointment_actions.php?id=<?php echo $next_appointment['appointment_id']; ?>" class="btn btn-success">Update Status</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Today's Patients -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-users"></i> Today's Patients</h5>
            </div>
            <div class="card-body">
                <?php if (empty($today_patients)): ?>
                    <p class="text-muted">No appointments scheduled for today.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Serial</th>
                                    <th>Patient</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Prescription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_patients as $patient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['appointment_serial'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['appointment_time']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                echo $patient['status'] === 'completed' ? 'bg-success' : 
                                                     ($patient['status'] === 'scheduled' ? 'bg-warning' : 'bg-secondary'); 
                                                ?>">
                                                <?php echo htmlspecialchars($patient['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($patient['prescription'])): ?>
                                                <span class="badge bg-info">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <?php if (!empty($patient['prescription'])): ?>
                                                <a href="prescription_print.php?appointment_id=<?php echo $patient['appointment_id']; ?>" class="btn btn-sm btn-outline-success">Print Rx</a>
                                            <?php endif; ?>
                                            <a href="appointment_actions.php?id=<?php echo $patient['appointment_id']; ?>" class="btn btn-sm btn-outline-success">Update</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Essential Tools -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-tools"></i> Essential Tools</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="prescriptions.php" class="btn btn-info w-100 py-3 animated-btn">
                            <i class="fas fa-prescription-bottle fa-lg me-2"></i>
                            <span>Manage Prescriptions</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="waiting_admin.php" class="btn btn-warning w-100 py-3 animated-btn">
                            <i class="fas fa-clock fa-lg me-2"></i>
                            <span>Waiting List</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="my_profile.php" class="btn btn-secondary w-100 py-3 animated-btn">
                            <i class="fas fa-user fa-lg me-2"></i>
                            <span>My Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<parameter name="filePath">c:\xampp\htdocs\clinicApp\pages\doctor_dashboard.php