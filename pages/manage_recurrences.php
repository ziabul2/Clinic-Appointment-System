<?php
$page_title = "Manage Recurrence Rules";
require_once __DIR__ . '/../config/config.php';

// Only allow admins and receptionists to manage recurrence rules
checkRole(['admin','receptionist']);

try {
    // Fetch doctors and patients for form
    $dq = $db->prepare('SELECT doctor_id, first_name, last_name FROM doctors ORDER BY first_name, last_name');
    $dq->execute();
    $doctors = $dq->fetchAll(PDO::FETCH_ASSOC);

    $pq = $db->prepare('SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name, last_name');
    $pq->execute();
    $patients = $pq->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing recurrence rules
    $rq = $db->prepare('SELECT * FROM recurrence_rules ORDER BY created_at DESC LIMIT 200');
    $rq->execute();
    $rules = $rq->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logAction('RECURRENCE_PAGE_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Unable to load recurrence data: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="fas fa-repeat"></i> Recurrence Rules</h1>
    <a href="appointments.php" class="btn btn-secondary">Back to Appointments</a>
</div>

<?php if (!empty($rules)): ?>
    <div class="card mb-4">
        <div class="card-header">Existing Rules</div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Doctor</th>
                        <th>Patient</th>
                        <th>Freq</th>
                        <th>Time</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $r): ?>
                        <tr>
                            <td><?php echo $r['recurrence_id']; ?></td>
                            <td><?php echo htmlspecialchars($r['doctor_id']); ?></td>
                            <td><?php echo htmlspecialchars($r['patient_id']); ?></td>
                            <td><?php echo htmlspecialchars($r['frequency'] . ' x' . intval($r['interval'] ?? 1)); ?></td>
                            <td><?php echo htmlspecialchars($r['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($r['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($r['end_date']); ?></td>
                            <td><?php echo $r['active'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">Create New Recurrence Rule</div>
    <div class="card-body">
        <form method="POST" action="../process.php?action=save_recurrence">
            <?php echo csrf_input(); ?>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="doctor_id" class="form-label">Doctor *</label>
                    <select id="doctor_id" name="doctor_id" class="form-select" required>
                        <option value="">Choose doctor...</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['doctor_id']; ?>">Dr. <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="patient_id" class="form-label">Patient *</label>
                    <select id="patient_id" name="patient_id" class="form-select" required>
                        <option value="">Choose patient...</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="frequency" class="form-label">Frequency *</label>
                    <select id="frequency" name="frequency" class="form-select" required>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="interval" class="form-label">Interval</label>
                    <input type="number" id="interval" name="interval" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="by_weekdays" class="form-label">By Weekdays (CSV)</label>
                    <input type="text" id="by_weekdays" name="by_weekdays" class="form-control" placeholder="MON,TUE">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="by_monthday" class="form-label">By Monthday (CSV)</label>
                    <input type="text" id="by_monthday" name="by_monthday" class="form-control" placeholder="1,15,28">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="appointment_time" class="form-label">Appointment Time *</label>
                    <input type="time" id="appointment_time" name="appointment_time" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="occurrences" class="form-label">Occurrences (limit)</label>
                    <input type="number" id="occurrences" name="occurrences" class="form-control" min="0" placeholder="0 = unlimited">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" value="15" min="5">
                </div>
            </div>

            <div class="mb-3">
                <label for="consultation_type" class="form-label">Consultation Type</label>
                <input type="text" id="consultation_type" name="consultation_type" class="form-control" placeholder="e.g. followup, checkup">
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="active" name="active" checked>
                <label class="form-check-label" for="active">Active</label>
            </div>

            <div class="d-flex justify-content-end">
                <a href="../pages/appointments.php" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Rule</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// end of file
?>
