<?php
$page_title = "View Patient";
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No patient specified.";
    redirect('patients.php');
}

$patient_id = sanitizeInput($_GET['id']);

try {
    $query = "SELECT * FROM patients WHERE patient_id = :patient_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        redirect('patients.php');
    }

    // Fetch appointments for this patient
    $appt_query = "SELECT a.*, d.first_name as doctor_first, d.last_name as doctor_last FROM appointments a 
                   LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                   WHERE a.patient_id = :patient_id ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $appt_stmt = $db->prepare($appt_query);
    $appt_stmt->bindParam(':patient_id', $patient_id);
    $appt_stmt->execute();
    $appointments = $appt_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction('VIEW_PATIENT_ERROR', $e->getMessage());
    $_SESSION['error'] = "Failed to load patient data.";
    redirect('patients.php');
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user"></i> Patient Details</h1>
    <div>
        <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
        <a href="patients.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header bg-light">Personal Info</div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($patient['address'])); ?></p>
                <p><strong>DOB:</strong> <?php echo $patient['date_of_birth'] ? date('M j, Y', strtotime($patient['date_of_birth'])) : '-'; ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($patient['emergency_contact']); ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header bg-light">Medical History</div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($patient['medical_history'])); ?></p>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-light">Appointments</div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <p class="text-muted">No appointments found for this patient.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($appointments as $a): ?>
                            <li class="list-group-item">
                                <strong><?php echo date('M j, Y', strtotime($a['appointment_date'])); ?></strong>
                                at <?php echo date('g:i A', strtotime($a['appointment_time'])); ?> with Dr. <?php echo htmlspecialchars($a['doctor_first'] . ' ' . $a['doctor_last']); ?>
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
