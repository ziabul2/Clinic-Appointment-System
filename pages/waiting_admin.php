<?php
$page_title = 'Waiting List - Admin';
require_once '../includes/header.php';
checkRole(['admin','receptionist']);

// handle flash messages via session
try {
    $q = $db->query("SELECT w.waiting_id, w.requested_at, w.status, w.notes, p.first_name, p.last_name, p.email, p.phone FROM waiting_list w JOIN patients p ON w.patient_id = p.patient_id WHERE w.status = 'waiting' ORDER BY w.requested_at ASC");
    $waitings = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logAction('WAITING_ADMIN_ERROR', $e->getMessage());
    $waitings = [];
}

?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5>Waiting List</h5>
                <div><a href="../pages/appointments.php" class="btn btn-light btn-sm">Appointments</a></div>
            </div>
            <div class="card-body">
                <?php if (empty($waitings)): ?>
                    <div class="alert alert-info">No patients are waiting right now.</div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Requested At</th><th>Notes</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php $i=1; foreach($waitings as $w): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($w['email']); ?></td>
                                <td><?php echo htmlspecialchars($w['phone']); ?></td>
                                <td><?php echo htmlspecialchars($w['requested_at']); ?></td>
                                <td><?php echo htmlspecialchars($w['notes']); ?></td>
                                <td>
                                    <form method="POST" action="../process.php?action=approve_waiting" class="d-inline">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="waiting_id" value="<?php echo $w['waiting_id']; ?>">
                                        <div class="input-group">
                                            <select name="doctor_id" class="form-control form-control-sm mr-1" required>
                                                <option value="">Select doctor</option>
                                                <?php
                                                    $dq = $db->query('SELECT doctor_id, first_name, last_name, consultation_fee FROM doctors');
                                                    foreach($dq->fetchAll(PDO::FETCH_ASSOC) as $doc) {
                                                        echo '<option value="'.intval($doc['doctor_id']).'">Dr. '.htmlspecialchars($doc['first_name'].' '.$doc['last_name']).' - '.htmlspecialchars($doc['consultation_fee']).' Taka</option>';
                                                    }
                                                ?>
                                            </select>
                                            <input type="date" name="appointment_date" class="form-control form-control-sm" required>
                                            <input type="time" name="appointment_time" class="form-control form-control-sm" required>
                                            <button class="btn btn-success btn-sm" type="submit">Approve</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
