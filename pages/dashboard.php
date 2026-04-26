<?php
$page_title = "Dashboard";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Get statistics
    $stats = [];
    
    // Total patients
    $query = "SELECT COUNT(*) as total FROM patients";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total doctors
    $query = "SELECT COUNT(*) as total FROM doctors";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_doctors'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Yesterday's appointments (for growth calculation)
    $query = "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['yesterday_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'scheduled'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total users
    $query = "SELECT COUNT(*) as total FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent appointments
    // Only show today's appointments (serials will indicate order)
    $query = "SELECT a.*, p.first_name, p.last_name, d.first_name as doctor_first, d.last_name as doctor_last 
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.patient_id 
              JOIN doctors d ON a.doctor_id = d.doctor_id 
              WHERE a.appointment_date = CURDATE() 
              ORDER BY a.appointment_serial ASC, a.appointment_time ASC 
              LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Waitlist count
    try {
        $q = $db->prepare("SELECT COUNT(*) as total FROM waiting_list WHERE status = 'pending'");
        $q->execute();
        $stats['waitlist_pending'] = $q->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['waitlist_pending'] = 0;
    }

    // New patients today (if patients.created_at exists)
    try {
        $q = $db->prepare("SELECT COUNT(*) as total FROM patients WHERE DATE(admitted_at) = CURDATE()");
        $q->execute();
        $stats['new_patients_today'] = $q->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['new_patients_today'] = 0;
    }

    // Appointments today by doctor (small summary)
    $query = "SELECT d.doctor_id, d.first_name, d.last_name, COUNT(a.appointment_id) as cnt FROM doctors d LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.appointment_date = CURDATE() GROUP BY d.doctor_id ORDER BY cnt DESC LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $appointments_by_doctor = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    logAction("DASHBOARD_ERROR", "Database error: " . $e->getMessage());
    $error = "Failed to load dashboard data.";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
<!-- Admin quick actions -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow mb-3">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">User Setup</h5>
                    <p class="text-muted mb-0">Manage system users and roles.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="users.php" class="btn btn-primary btn-lg animated-btn"><i class="fas fa-users-cog"></i> Manage Users</a>
                    <a href="add_user.php" class="btn btn-outline-primary btn-lg animated-btn"><i class="fas fa-user-plus"></i> Add User</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'doctor'): ?>
<!-- Doctor quick view -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow mb-3">
            <div class="card-body">
                <h5 class="mb-3">My Appointments</h5>
                <?php
                    // If the users table links doctor_id, try to determine current doctor's id
                    $doctorAppointments = [];
                    $doctorId = null;
                    if (!empty($_SESSION['user_id'])) {
                        $q = $db->prepare('SELECT doctor_id FROM users WHERE user_id = :uid');
                        $q->bindParam(':uid', $_SESSION['user_id']);
                        $q->execute();
                        $r = $q->fetch(PDO::FETCH_ASSOC);
                        if ($r && !empty($r['doctor_id'])) $doctorId = $r['doctor_id'];
                    }
                    if ($doctorId) {
                        // Show only today's patients for doctor's quick view
                        $qa = $db->prepare('SELECT a.*, p.first_name, p.last_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.doctor_id = :did AND a.appointment_date = CURDATE() ORDER BY a.appointment_serial ASC, a.appointment_time ASC LIMIT 20');
                        $qa->bindParam(':did', $doctorId);
                        $qa->execute();
                        $doctorAppointments = $qa->fetchAll(PDO::FETCH_ASSOC);
                    }
                ?>
                <?php if (empty($doctorAppointments)): ?>
                    <p class="text-muted">No appointments found for your account.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($doctorAppointments as $ap): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ap['first_name'].' '.$ap['last_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($ap['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($ap['appointment_time'])); ?></td>
                                        <td><span class="badge bg-<?php echo $ap['status']=='scheduled'?'primary':($ap['status']=='completed'?'success':'danger'); ?>"><?php echo ucfirst($ap['status']); ?></span></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view_patient.php?id=<?php echo $ap['patient_id']; ?>" class="btn btn-sm btn-outline-secondary" title="View Patient"><i class="fas fa-user"></i></a>
                                                <a href="prescription_edit.php?appointment_id=<?php echo $ap['appointment_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Prescription"><i class="fas fa-file-prescription"></i></a>
                                                <form method="post" action="../process.php?action=send_prescription_mail" class="d-inline ms-1 send-presc-form">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="appointment_id" value="<?php echo $ap['appointment_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Send Prescription"><i class="fas fa-paper-plane"></i></button>
                                                </form>
                                                <a href="prescription_print.php?appointment_id=<?php echo $ap['appointment_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary ms-1" title="Print"><i class="fas fa-print"></i></a>
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
    </div>
</div>
<?php endif; ?>

<!-- Statistics Summary -->
<div class="row g-3 mt-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Patients</div>
                <div class="value"><?php echo $stats['total_patients']; ?></div>
            </div>
            <div class="stat-icon bg-primary text-white">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Doctors</div>
                <div class="value"><?php echo $stats['total_doctors']; ?></div>
            </div>
            <div class="stat-icon bg-success text-white">
                <i class="fas fa-user-md"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Today's Appointments</div>
                <div class="value"><?php echo $stats['today_appointments']; ?></div>
            </div>
            <div class="stat-icon bg-info text-white">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Pending</div>
                <div class="value"><?php echo $stats['pending_appointments']; ?></div>
            </div>
            <div class="stat-icon bg-warning text-white">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">New Patients Today</div>
                <div class="value"><?php echo intval($stats['new_patients_today']); ?></div>
            </div>
            <div class="stat-icon bg-info text-white">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Growth vs Yesterday</div>
                <div class="value">
                    <?php
                        $today = intval($stats['today_appointments']);
                        $yesterday = intval($stats['yesterday_appointments']);
                        if ($yesterday == 0) {
                            echo $today > 0 ? '+100%' : '0%';
                        } else {
                            $pct = round((($today - $yesterday) / max(1, $yesterday)) * 100, 1);
                            echo ($pct >= 0 ? '+' . $pct . '%' : $pct . '%');
                        }
                    ?>
                </div>
            </div>
            <div class="stat-icon bg-secondary text-white">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Waitlist Pending</div>
                <div class="value"><?php echo intval($stats['waitlist_pending']); ?></div>
            </div>
            <div class="stat-icon bg-dark text-white">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Top Doctors</div>
                <div class="small mt-1">
                    <?php foreach (array_slice($appointments_by_doctor, 0, 2) as $d) { echo '<div class="text-truncate" style="max-width:120px;">' . htmlspecialchars('Dr. ' . $d['first_name']) . ' (' . intval($d['cnt']) . ')</div>'; } ?>
                </div>
            </div>
            <div class="stat-icon bg-primary text-white">
                <i class="fas fa-award"></i>
            </div>
        </div>
    </div>
</div>

<!-- Today's Appointments -->
<div class="list-container mt-4">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Today's Appointments</h5>
    </div>
    <div class="list-body">
        <?php if (empty($recent_appointments)): ?>
            <div class="p-3 text-muted">No recent appointments found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:8%;">Serial</th>
                            <th style="width:20%;">Patient</th>
                            <th style="width:20%;">Doctor</th>
                            <th style="width:12%;">Date</th>
                            <th style="width:12%;">Time</th>
                            <th style="width:12%;">Status</th>
                            <th style="width:16%;">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_serial'] ?? '-'); ?></td>
                                <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                                <td>Dr. <?php echo $appointment['doctor_first'] . ' ' . $appointment['doctor_last']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td><span class="badge bg-<?php 
                                    switch($appointment['status']) {
                                        case 'scheduled': echo 'primary'; break;
                                        case 'completed': echo 'success'; break;
                                        case 'cancelled': echo 'danger'; break;
                                        default: echo 'warning';
                                    }
                                ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span></td>
                                <td><?php echo ucfirst(str_replace('-', ' ', $appointment['consultation_type'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>