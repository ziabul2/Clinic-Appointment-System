<?php
$page_title = "View Doctor";
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No doctor specified.";
    redirect('doctors.php');
}

$doctor_id = sanitizeInput($_GET['id']);

try {
    $query = "SELECT * FROM doctors WHERE doctor_id = :doctor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->execute();
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        $_SESSION['error'] = "Doctor not found.";
        redirect('doctors.php');
    }

    // Fetch appointments for this doctor
    $appt_query = "SELECT a.*, p.first_name as patient_first, p.last_name as patient_last FROM appointments a 
                   LEFT JOIN patients p ON a.patient_id = p.patient_id
                   WHERE a.doctor_id = :doctor_id ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $appt_stmt = $db->prepare($appt_query);
    $appt_stmt->bindParam(':doctor_id', $doctor_id);
    $appt_stmt->execute();
    $appointments = $appt_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction('VIEW_DOCTOR_ERROR', $e->getMessage());
    $_SESSION['error'] = "Failed to load doctor data.";
    redirect('doctors.php');
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-md"></i> Doctor Details</h1>
    <div>
        <a href="edit_doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
        <a href="doctors.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header bg-light">Profile</div>
            <div class="card-body">
                <?php if (!empty($doctor['profile_picture']) && file_exists(__DIR__ . '/../uploads/doctors/' . $doctor['profile_picture'])): ?>
                    <div class="text-center mb-3">
                        <img src="../uploads/doctors/<?php echo htmlspecialchars($doctor['profile_picture']); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['first_name']); ?>" class="rounded-circle" style="width:120px;height:120px;object-fit:cover;">
                    </div>
                <?php endif; ?>
                <p><strong>Name:</strong> Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
                <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                <p><strong>License No:</strong> <?php echo htmlspecialchars($doctor['license_number']); ?></p>
                <p><strong>Available:</strong> <?php echo htmlspecialchars($doctor['available_days']) . ' (' . htmlspecialchars($doctor['available_time_start'] . ' - ' . $doctor['available_time_end']) . ')'; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-light">Appointments</div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <p class="text-muted">No appointments found for this doctor.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($appointments as $a): ?>
                            <li class="list-group-item">
                                <strong><?php echo date('M j, Y', strtotime($a['appointment_date'])); ?></strong>
                                at <?php echo date('g:i A', strtotime($a['appointment_time'])); ?> with <?php echo htmlspecialchars($a['patient_first'] . ' ' . $a['patient_last']); ?>
                                <div><small><?php echo htmlspecialchars(ucfirst($a['status'])); ?> - <?php echo htmlspecialchars($a['consultation_type']); ?></small></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
