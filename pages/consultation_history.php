<?php
/**
 * Consultation History Page
 */
$page_title = "Consultation History";
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$patient = null;
$history = [];

if ($patient_id) {
    try {
        $q = $db->prepare("SELECT * FROM patients WHERE patient_id = :id");
        $q->execute(['id' => $patient_id]);
        $patient = $q->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            $q = $db->prepare("
                SELECT ch.*, d.first_name as dfn, d.last_name as dln 
                FROM consultation_history ch
                LEFT JOIN doctors d ON ch.doctor_id = d.doctor_id
                WHERE ch.patient_id = :pid
                ORDER BY ch.consultation_date DESC
            ");
            $q->execute(['pid' => $patient_id]);
            $history = $q->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-file-medical-alt text-primary me-2"></i>Consultation History</h2>
            <?php if ($patient): ?>
                <p class="text-muted">History for <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong></p>
            <?php else: ?>
                <p class="text-muted">Detailed logs of all medical consultations.</p>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($patient_id): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConsultationModal">
                    <i class="fas fa-plus me-2"></i>New Consultation
                </button>
            <?php endif; ?>
            <a href="patients.php" class="btn btn-outline-secondary ms-2">Back</a>
        </div>
    </div>

    <?php if (!$patient_id): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Search Patient to View History</label>
                        <select name="patient_id" class="form-select select2" required>
                            <option value="">Select a patient...</option>
                            <?php
                            $pats = $db->query("SELECT patient_id, first_name, last_name, phone FROM patients ORDER BY first_name ASC")->fetchAll();
                            foreach ($pats as $p) {
                                echo "<option value='{$p['patient_id']}'>".htmlspecialchars($p['first_name']." ".$p['last_name'])." ({$p['phone']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View History</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Main Symptom</th>
                                    <th>Specialty Rec.</th>
                                    <th>Severity</th>
                                    <th>Doctor</th>
                                    <th>AI Conf.</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-notes-medical fa-2x mb-3"></i><br>
                                            No consultation history found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <small class="fw-bold"><?php echo date('M d, Y', strtotime($h['consultation_date'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($h['consultation_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="fw-bold"><?php echo htmlspecialchars($h['main_symptom']); ?></span>
                                                <?php if ($h['additional_symptoms']): ?>
                                                    <i class="fas fa-info-circle text-info ms-1" title="<?php echo htmlspecialchars($h['additional_symptoms']); ?>"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border"><?php echo htmlspecialchars($h['recommended_specialty'] ?: 'N/A'); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $sevClass = 'bg-success';
                                                if (strtolower($h['severity']) == 'medium') $sevClass = 'bg-warning text-dark';
                                                if (strtolower($h['severity']) == 'high') $sevClass = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $sevClass; ?>"><?php echo ucfirst($h['severity']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($h['dfn']): ?>
                                                    Dr. <?php echo htmlspecialchars($h['dfn'] . ' ' . $h['dln']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 6px; width: 60px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo ($h['ai_confidence']*100); ?>%;"></div>
                                                </div>
                                                <small><?php echo ($h['ai_confidence']*100); ?>%</small>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-info" onclick="viewDetails(<?php echo $h['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Consultation Modal -->
<div class="modal fade" id="addConsultationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="../process.php?action=save_consultation" method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">New Digital Consultation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Main Symptom</label>
                            <input type="text" name="main_symptom" class="form-control" placeholder="e.g. Fever, Chest Pain" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Severity</label>
                            <select name="severity" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Additional Symptoms</label>
                            <textarea name="additional_symptoms" class="form-control" rows="3" placeholder="List other symptoms..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Recommended Specialty</label>
                            <input type="text" name="recommended_specialty" class="form-control" placeholder="e.g. Cardiology">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">AI Confidence Score (0.0 - 1.0)</label>
                            <input type="number" step="0.1" min="0" max="1" name="ai_confidence" class="form-control" value="0.9">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Doctor's Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Consultation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewDetails(id) {
    // Implement detailed view if needed
    alert('Detail view for ID: ' + id);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
