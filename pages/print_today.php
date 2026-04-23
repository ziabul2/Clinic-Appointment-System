<?php
$page_title = "Print Today's Appointments";
require_once '../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');

$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

try {
    // Base query: today's appointments
    $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.appointment_serial,
                   p.first_name AS patient_first, p.last_name AS patient_last,
                   d.first_name AS doctor_first, d.last_name AS doctor_last
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.patient_id
            LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
            WHERE DATE(a.appointment_date) = CURDATE()";

    // If doctor role, limit to their doctor_id
    if ($role === 'doctor' && !empty($_SESSION['doctor_id'])) {
        $sql .= " AND a.doctor_id = :docid";
        $stmt = $db->prepare($sql . " ORDER BY a.appointment_time ASC");
        $stmt->bindParam(':docid', $_SESSION['doctor_id']);
    } else {
        $stmt = $db->prepare($sql . " ORDER BY a.appointment_time ASC");
    }

    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to load appointments: ' . $e->getMessage();
    $appointments = [];
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Today's Appointments</h5>
        <div>
            <a href="../pages/appointments.php" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">No appointments scheduled for today.</div>
        <?php else: ?>
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Serial</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['appointment_id']); ?></td>
                            <td><?php echo date('h:i A', strtotime($a['appointment_time'])); ?></td>
                            <td><?php echo !empty($a['appointment_serial']) ? sprintf('%03d', $a['appointment_serial']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($a['patient_first'] . ' ' . $a['patient_last']); ?></td>
                            <td><?php echo 'Dr. ' . htmlspecialchars($a['doctor_first'] . ' ' . $a['doctor_last']); ?></td>
                            <td class="no-print">
                                <a class="btn btn-sm btn-primary" href="print_appointment.php?id=<?php echo urlencode($a['appointment_id']); ?>" target="_blank"><i class="fas fa-print"></i> Print</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
