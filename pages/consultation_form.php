<?php
$page_title = "Medical Consultation";
require_once '../includes/header.php';
if (!isLoggedIn()) redirect('../index.php');

$appointment_id = $_GET['appointment_id'] ?? null;
$appointment = null;
$patient = null;

if ($appointment_id) {
    $stmt = $db->prepare("SELECT a.*, p.first_name, p.last_name, p.patient_id, p.date_of_birth, p.gender, p.allergies, p.medical_history 
                         FROM appointments a 
                         JOIN patients p ON a.patient_id = p.patient_id 
                         WHERE a.appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$appointment) {
    echo '<div class="alert alert-danger mt-4">Appointment not found. <a href="dashboard.php">Go back to dashboard</a></div>';
    require_once '../includes/footer.php';
    exit;
}

// Calculate age
$age_display = 'Not Set';
if (!empty($appointment['date_of_birth']) && $appointment['date_of_birth'] !== '0000-00-00') {
    $dob = new DateTime($appointment['date_of_birth']);
    $now = new DateTime();
    $age_display = $now->diff($dob)->y . ' Years';
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-auto">
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        <div class="col">
            <h1 class="h3 fw-bold text-dark mb-0">Clinical Consultation</h1>
        </div>
    </div>

    <form action="../process.php?action=save_consultation" method="POST">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
        <input type="hidden" name="patient_id" value="<?php echo $appointment['patient_id']; ?>">
        <input type="hidden" name="redirect" value="dashboard">

        <div class="row g-4">
            <!-- Patient Brief Info -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 mb-4 bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-white text-primary me-3">
                                <i class="fas fa-user fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h5>
                                <p class="mb-0 opacity-75 small">Patient ID: #<?php echo $appointment['patient_id']; ?></p>
                            </div>
                        </div>
                        <hr class="opacity-25">
                        <div class="row g-2 small">
                            <div class="col-6"><strong>Age:</strong> <?php echo $age_display; ?></div>
                            <div class="col-6"><strong>Gender:</strong> <?php echo htmlspecialchars($appointment['gender']); ?></div>
                            <div class="col-12"><strong>Specialty:</strong> <?php echo htmlspecialchars($appointment['specialty'] ?? 'General'); ?></div>
                        </div>
                        <div class="mt-3 p-2 bg-white bg-opacity-10 rounded-3 small">
                            <div class="mb-1"><i class="fas fa-exclamation-triangle me-1 text-warning"></i> <strong>Allergies:</strong></div>
                            <div class="opacity-75"><?php echo !empty($appointment['allergies']) ? htmlspecialchars($appointment['allergies']) : 'None Reported'; ?></div>
                        </div>
                        <div class="mt-2 p-2 bg-white bg-opacity-10 rounded-3 small">
                            <div class="mb-1"><i class="fas fa-file-medical me-1"></i> <strong>Medical History:</strong></div>
                            <div class="opacity-75"><?php echo !empty($appointment['medical_history']) ? htmlspecialchars($appointment['medical_history']) : 'No history found'; ?></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-history me-2 text-info"></i>Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="consultation_history.php?patient_id=<?php echo $appointment['patient_id']; ?>" target="_blank" class="btn btn-outline-info rounded-pill text-start">
                                <i class="fas fa-external-link-alt me-2"></i>View Full History
                            </a>
                            <a href="prescription_edit.php?appointment_id=<?php echo $appointment_id; ?>" target="_blank" class="btn btn-outline-success rounded-pill text-start">
                                <i class="fas fa-prescription me-2"></i>Open Rx Editor
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consultation Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-heartbeat me-2 text-danger"></i>Patient Vitals</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Blood Pressure</label>
                                <input type="text" name="bp" class="form-control rounded-3" value="<?php echo htmlspecialchars($appointment['bp'] ?? ''); ?>" placeholder="120/80">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Pulse (BPM)</label>
                                <input type="text" name="pulse" class="form-control rounded-3" value="<?php echo htmlspecialchars($appointment['pulse'] ?? ''); ?>" placeholder="72">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Weight (kg)</label>
                                <input type="text" name="weight" class="form-control rounded-3" value="<?php echo htmlspecialchars($appointment['weight'] ?? ''); ?>" placeholder="70">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Temperature (°F)</label>
                                <input type="text" name="temperature" class="form-control rounded-3" value="<?php echo htmlspecialchars($appointment['temperature'] ?? ''); ?>" placeholder="98.6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">SpO2 (%)</label>
                                <input type="text" name="spo2" class="form-control rounded-3" value="<?php echo htmlspecialchars($appointment['spo2'] ?? ''); ?>" placeholder="98">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-notes-medical me-2 text-primary"></i>Clinical Findings</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Main Symptom</label>
                                <input type="text" name="main_symptom" id="mainSymptomInput" class="form-control rounded-3" required 
                                       value="<?php echo htmlspecialchars($appointment['symptoms'] ?? ''); ?>" placeholder="Primary complaint">
                                <div id="specialtySuggestion" class="small text-info mt-1" style="display:none;">
                                    <i class="fas fa-lightbulb me-1"></i> Recommended: <span id="suggestedSpecText"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Recommended Specialty</label>
                                <input type="text" name="recommended_specialty" id="recSpecialty" class="form-control rounded-3" placeholder="e.g. Cardiology">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Severity Level</label>
                                <select name="severity" class="form-select rounded-3">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Diagnosis</label>
                                <textarea name="diagnosis" class="form-control rounded-3" rows="3" placeholder="Clinical diagnosis..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Treatment Plan / Advice</label>
                                <textarea name="treatment_plan" class="form-control rounded-3" rows="4" placeholder="Advised treatment and next steps..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-5">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow">
                        <i class="fas fa-save me-2"></i>Save Consultation Record
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .avatar-circle { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .form-control:focus, .form-select:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Specialty Suggestion Logic
    const mainSymptomInput = document.getElementById('mainSymptomInput');
    const specialtySuggestion = document.getElementById('specialtySuggestion');
    const suggestedSpecText = document.getElementById('suggestedSpecText');
    const recSpecialtyInput = document.getElementById('recSpecialty');

    if (mainSymptomInput) {
        let debounceTimer;
        mainSymptomInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length < 3) {
                specialtySuggestion.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`../process.php?action=suggest_specialty&symptom=${encodeURIComponent(val)}`, {credentials: 'same-origin'})
                .then(r => r.json())
                .then(res => {
                    if (res.ok && res.specialties && res.specialties.length > 0) {
                        suggestedSpecText.textContent = res.specialties.join(', ');
                        specialtySuggestion.style.display = 'block';
                        
                        if (recSpecialtyInput && !recSpecialtyInput.value) {
                            recSpecialtyInput.value = res.specialties[0];
                        }
                    } else {
                        specialtySuggestion.style.display = 'none';
                    }
                });
            }, 500);
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
