<?php
$page_title = "My Appointments";
require_once '../includes/header.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'Doctor') {
    $_SESSION['error'] = 'Access denied.';
    redirect('../index.php');
}

// Ensure users table has doctor_id column
$user_id = $_SESSION['user_id'];
try {
    $q = $db->prepare("SELECT doctor_id FROM users WHERE user_id = :uid");
    $q->bindParam(':uid', $user_id);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $row['doctor_id'] ?? null;
    if (!$doctor_id) {
        $_SESSION['error'] = 'No doctor profile linked to your user account. Ask admin to link you to a doctor profile.';
        redirect('pages/dashboard.php');
    }

    $stmt = $db->prepare("SELECT a.*, p.first_name, p.last_name FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id WHERE a.doctor_id = :doc ORDER BY a.appointment_date DESC, a.appointment_time DESC");
    $stmt->bindParam(':doc', $doctor_id);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logAction('MY_APPTS_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Failed to load appointments.';
    redirect('pages/dashboard.php');
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-check"></i> My Appointments</h1>
    <div>
        <a href="pages/dashboard.php" class="btn btn-secondary">Dashboard</a>
    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <p class="text-muted">No appointments scheduled.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark"><tr><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a['first_name'].' '.$a['last_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($a['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($a['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($a['status'])); ?></td>
                                <td><?php echo htmlspecialchars($a['notes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
