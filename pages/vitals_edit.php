<?php
$page_title = "Patient Vitals";
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

if (!$appointment_id) {
    $_SESSION['error'] = 'Invalid appointment ID.';
    redirect('dashboard.php');
}

// Fetch appointment and patient details
$q = $db->prepare("SELECT a.*, p.first_name, p.last_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_id = :id");
$q->execute(['id' => $appointment_id]);
$appointment = $q->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found.';
    redirect('dashboard.php');
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-heartbeat text-danger"></i> Patient Vitals</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Enter Vitals for <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h5>
            </div>
            <div class="card-body">
                <form action="../process.php?action=save_vitals" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                    
                    <div class="mb-4 text-center">
                        <span class="badge bg-light text-dark border p-2">Serial: #<?php echo $appointment['appointment_serial'] ?? 'N/A'; ?></span>
                        <span class="badge bg-light text-dark border p-2">Date: <?php echo $appointment['appointment_date']; ?></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Blood Pressure (BP)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-gauge-high"></i></span>
                            <input type="text" class="form-control form-control-lg" name="bp" placeholder="e.g. 120/80" value="<?php echo htmlspecialchars($appointment['bp'] ?? ''); ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pulse Rate (bpm)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-heart"></i></span>
                            <input type="text" class="form-control form-control-lg" name="pulse" placeholder="e.g. 72" value="<?php echo htmlspecialchars($appointment['pulse'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Weight (kg)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-weight"></i></span>
                            <input type="text" class="form-control form-control-lg" name="weight" placeholder="e.g. 70" value="<?php echo htmlspecialchars($appointment['weight'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Temperature (°F)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-thermometer-half"></i></span>
                                <input type="text" class="form-control form-control-lg" name="temperature" placeholder="98.6" value="<?php echo htmlspecialchars($appointment['temperature'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">SpO2 (%)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lungs"></i></span>
                                <input type="text" class="form-control form-control-lg" name="spo2" placeholder="98" value="<?php echo htmlspecialchars($appointment['spo2'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Vitals</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-4 alert alert-info small">
            <i class="fas fa-info-circle"></i> Vitals will be automatically included in the doctor's prescription template.
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
