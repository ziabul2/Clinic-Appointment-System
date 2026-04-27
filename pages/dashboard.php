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

    // Doctor-specific statistics if applicable
    $isDoctor = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'doctor');
    $doctorStats = [];
    $currentDoctorId = null;
    $doctorData = null;

    if ($isDoctor) {
        // Find doctor_id
        $q = $db->prepare('SELECT d.* FROM doctors d JOIN users u ON d.doctor_id = u.doctor_id WHERE u.user_id = :uid');
        $q->bindParam(':uid', $_SESSION['user_id']);
        $q->execute();
        $doctorData = $q->fetch(PDO::FETCH_ASSOC);
        
        if ($doctorData) {
            $currentDoctorId = $doctorData['doctor_id'];
            
            // Total patients for this doctor
            $q = $db->prepare("SELECT COUNT(DISTINCT patient_id) as total FROM appointments WHERE doctor_id = :did");
            $q->bindParam(':did', $currentDoctorId);
            $q->execute();
            $doctorStats['total_patients'] = $q->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Today's appointments for this doctor
            $q = $db->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :did AND appointment_date = CURDATE()");
            $q->bindParam(':did', $currentDoctorId);
            $q->execute();
            $doctorStats['today_appointments'] = $q->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Pending today
            $q = $db->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :did AND appointment_date = CURDATE() AND status = 'scheduled'");
            $q->bindParam(':did', $currentDoctorId);
            $q->execute();
            $doctorStats['pending_today'] = $q->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Completed today
            $q = $db->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = :did AND appointment_date = CURDATE() AND status = 'completed'");
            $q->bindParam(':did', $currentDoctorId);
            $q->execute();
            $doctorStats['completed_today'] = $q->fetch(PDO::FETCH_ASSOC)['total'];

            // Fetch today's appointments for the table
            $q = $db->prepare("SELECT a.*, p.first_name, p.last_name, p.phone FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.doctor_id = :did AND a.appointment_date = CURDATE() ORDER BY a.appointment_serial ASC, a.appointment_time ASC");
            $q->bindParam(':did', $currentDoctorId);
            $q->execute();
            $doctorAppointments = $q->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
} catch (PDOException $e) {
    logAction("DASHBOARD_ERROR", "Database error: " . $e->getMessage());
    $error = "Failed to load dashboard data.";
}
?>

<?php if ($isDoctor): ?>
    <!-- Doctor Hero Section -->
    <div class="dashboard-hero">
        <div class="row align-items-center">
            <div class="col-md-8 welcome-text">
                <h1>Welcome back, Dr. <?php echo htmlspecialchars($doctorData['last_name'] ?? $_SESSION['username']); ?>!</h1>
                <p>You have <?php echo $doctorStats['pending_today']; ?> patients waiting for you today. Let's provide some great care!</p>
                <div class="mt-4 d-flex gap-3">
                    <a href="appointments.php" class="btn btn-light btn-lg shadow-sm"><i class="fas fa-calendar-alt"></i> View Schedule</a>
                    <a href="prescriptions.php" class="btn btn-outline-light btn-lg"><i class="fas fa-file-medical"></i> Prescriptions</a>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-block text-end">
                <i class="fas fa-user-md fa-8x opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Doctor Stats & Quick Actions -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="row g-4">
                <div class="col-sm-6">
                    <div class="doctor-stat-card stat-card-blue">
                        <div class="icon-box bg-white text-primary"><i class="fas fa-calendar-day"></i></div>
                        <div>
                            <div class="stat-label">Today's Appointments</div>
                            <div class="stat-value"><?php echo $doctorStats['today_appointments']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="doctor-stat-card stat-card-purple">
                        <div class="icon-box bg-white text-purple"><i class="fas fa-user-clock"></i></div>
                        <div>
                            <div class="stat-label">Pending Patients</div>
                            <div class="stat-value"><?php echo $doctorStats['pending_today']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="doctor-stat-card stat-card-green">
                        <div class="icon-box bg-white text-success"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-label">Completed Today</div>
                            <div class="stat-value"><?php echo $doctorStats['completed_today']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="doctor-stat-card stat-card-orange">
                        <div class="icon-box bg-white text-orange"><i class="fas fa-hospital-user"></i></div>
                        <div>
                            <div class="stat-label">Total Patients Seen</div>
                            <div class="stat-value"><?php echo $doctorStats['total_patients']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="quick-actions-card shadow-sm">
                <h5 class="mb-4 fw-bold">Quick Actions</h5>
                <a href="prescriptions.php" class="quick-action-btn btn-qa-primary">
                    <i class="fas fa-file-prescription"></i>
                    <span>Write Prescription</span>
                </a>
                <a href="add_patient.php" class="quick-action-btn btn-qa-success">
                    <i class="fas fa-user-plus"></i>
                    <span>Register New Patient</span>
                </a>
                <a href="appointments.php" class="quick-action-btn btn-qa-info">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
                <div class="mt-4 pt-3 border-top">
                    <form action="patients.php" method="GET" class="position-relative">
                        <input type="text" name="search" class="form-control ps-5" placeholder="Search patient...">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor's Today's Appointments -->
    <div class="list-container">
        <div class="list-header">
            <h5 class="mb-0 fw-bold"><i class="fas fa-list-ul"></i> Your Schedule for Today</h5>
            <span class="badge bg-primary rounded-pill"><?php echo count($doctorAppointments); ?> Total</span>
        </div>
        <div class="list-body">
            <?php if (empty($doctorAppointments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-check fa-4x text-light mb-3"></i>
                    <p class="text-muted">You have no appointments scheduled for today.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctorAppointments as $ap): ?>
                                <tr>
                                    <td><span class="fw-bold text-primary"><?php echo $ap['appointment_serial'] ?? '-'; ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                                <i class="fas fa-user text-muted small"></i>
                                            </div>
                                            <span class="fw-bold"><?php echo htmlspecialchars($ap['first_name'] . ' ' . $ap['last_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($ap['phone'] ?? '-'); ?></td>
                                    <td><i class="far fa-clock me-1 text-muted"></i> <?php echo date('g:i A', strtotime($ap['appointment_time'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $ap['status'] == 'completed' ? 'success' : ($ap['status'] == 'cancelled' ? 'danger' : 'primary'); 
                                        ?> rounded-pill">
                                            <?php echo ucfirst($ap['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="view_patient.php?id=<?php echo $ap['patient_id']; ?>" class="btn btn-sm btn-outline-secondary" title="View Patient Profile"><i class="fas fa-user"></i></a>
                                            <a href="prescription_edit.php?appointment_id=<?php echo $ap['appointment_id']; ?>" class="btn btn-sm btn-outline-primary" title="Prescribe"><i class="fas fa-prescription"></i></a>
                                            <a href="prescription_print.php?appointment_id=<?php echo $ap['appointment_id']; ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print"><i class="fas fa-print"></i></a>
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

<?php else: ?>
    <!-- Existing Dashboard for Admins/Others -->
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
                        <h5 class="mb-1 text-dark">User Setup</h5>
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
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>