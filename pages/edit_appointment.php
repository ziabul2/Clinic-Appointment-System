<?php
$page_title = "Edit Appointment";
require_once '../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'No appointment specified.';
    redirect('appointments.php');
}
$appointment_id = intval($_GET['id']);

try {
    $q = $db->prepare('SELECT a.*, p.first_name AS pfn, p.last_name AS pln, p.patient_id, d.first_name AS dfn, d.last_name AS dln, d.doctor_id FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id LEFT JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.appointment_id = :id LIMIT 1');
    $q->bindParam(':id', $appointment_id);
    $q->execute();
    if ($q->rowCount() == 0) {
        $_SESSION['error'] = 'Appointment not found.';
        redirect('appointments.php');
    }
    $apt = $q->fetch(PDO::FETCH_ASSOC);

    // fetch patients and doctors for selects
    $patients = $db->query('SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name')->fetchAll(PDO::FETCH_ASSOC);
    $doctors = $db->query('SELECT doctor_id, first_name, last_name FROM doctors ORDER BY first_name')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logAction('EDIT_APPOINTMENT_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Failed to load appointment.';
    redirect('appointments.php');
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2">Edit Appointment #<?php echo $appointment_id; ?></h1>
    <a href="appointments.php" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="../process.php?action=edit_appointment">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['patient_id']; ?>" <?php echo ($p['patient_id']==$apt['patient_id'])? 'selected' : ''; ?>><?php echo htmlspecialchars($p['first_name'].' '.$p['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select" required>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['doctor_id']; ?>" <?php echo ($d['doctor_id']==$apt['doctor_id'])? 'selected' : ''; ?>>Dr. <?php echo htmlspecialchars($d['first_name'].' '.$d['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="appointment_date" class="form-control" value="<?php echo htmlspecialchars($apt['appointment_date']); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Time</label>
                    <input type="time" name="appointment_time" class="form-control" value="<?php echo htmlspecialchars($apt['appointment_time']); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Consultation Type</label>
                    <select name="consultation_type" class="form-select">
                        <option value="in-person" <?php echo ($apt['consultation_type']=='in-person')? 'selected':''; ?>>In-person</option>
                        <option value="telemedicine" <?php echo ($apt['consultation_type']=='telemedicine')? 'selected':''; ?>>Telemedicine</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Symptoms</label>
                <textarea name="symptoms" class="form-control"><?php echo htmlspecialchars($apt['symptoms'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control"><?php echo htmlspecialchars($apt['notes'] ?? ''); ?></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <a href="appointments.php" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>