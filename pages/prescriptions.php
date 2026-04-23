<?php
$page_title = 'Prescriptions';
require_once '../includes/header.php';
if (!isLoggedIn()) redirect('../index.php');

try {
    // If the user is a doctor, show only their prescriptions; otherwise show all
    $role = strtolower($_SESSION['role'] ?? '');
    if ($role === 'doctor' && !empty($_SESSION['doctor_id'])) {
        $stmt = $db->prepare('SELECT p.*, a.appointment_date, a.appointment_time, d.first_name AS dfn, d.last_name AS dln, pt.first_name AS pfn, pt.last_name AS pln FROM prescriptions p LEFT JOIN appointments a ON p.appointment_id = a.appointment_id LEFT JOIN doctors d ON p.doctor_id = d.doctor_id LEFT JOIN patients pt ON p.patient_id = pt.patient_id WHERE p.doctor_id = :did ORDER BY p.created_at DESC');
        $stmt->bindParam(':did', $_SESSION['doctor_id']);
        $stmt->execute();
    } else {
        $stmt = $db->query('SELECT p.*, a.appointment_date, a.appointment_time, d.first_name AS dfn, d.last_name AS dln, pt.first_name AS pfn, pt.last_name AS pln FROM prescriptions p LEFT JOIN appointments a ON p.appointment_id = a.appointment_id LEFT JOIN doctors d ON p.doctor_id = d.doctor_id LEFT JOIN patients pt ON p.patient_id = pt.patient_id ORDER BY p.created_at DESC');
    }
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $prescriptions = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Prescriptions</h1>
    <a href="dashboard.php" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($prescriptions)): ?>
            <p class="text-muted">No prescriptions found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>ID</th><th>Appointment</th><th>Patient</th><th>Doctor</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($prescriptions as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['appointment_date'].' '.$p['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($p['pfn'].' '.$p['pln']); ?></td>
                            <td><?php echo htmlspecialchars($p['dfn'].' '.$p['dln']); ?></td>
                            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
                            <td>
                                <a href="prescription_edit.php?appointment_id=<?php echo $p['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="prescription_print.php?appointment_id=<?php echo $p['appointment_id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
