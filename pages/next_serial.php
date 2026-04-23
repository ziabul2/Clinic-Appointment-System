<?php
$page_title = "Next Appointment Serial";
require_once __DIR__ . '/../includes/header.php';

// Only admin and receptionist may view this tool
if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['admin','receptionist'])) {
    $_SESSION['error'] = 'Unauthorized access.';
    redirect('index.php');
}

$selected_date = $_POST['date'] ?? ($_GET['date'] ?? date('Y-m-d'));
$next_serial = null;
try {
    if (!empty($selected_date)) {
        $q = $db->prepare('SELECT last_serial FROM appointment_counters WHERE `date` = :date LIMIT 1');
        $q->bindParam(':date', $selected_date);
        $q->execute();
        if ($q->rowCount() > 0) {
            $r = $q->fetch(PDO::FETCH_ASSOC);
            $last = intval($r['last_serial']);
            $next_serial = $last + 1;
        } else {
            $next_serial = 1;
        }
    }
} catch (Exception $e) {
    logAction('NEXT_SERIAL_ERROR', 'Error computing next serial: ' . $e->getMessage());
    $_SESSION['error'] = 'Could not compute next serial.';
}
?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Next Appointment Serial</strong>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <?php echo csrf_input(); ?>
                    <div class="col-auto">
                        <label for="date" class="col-form-label">Date</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" type="submit">Check</button>
                    </div>
                </form>

                <?php if ($next_serial !== null): ?>
                    <hr>
                    <p>Next serial for <strong><?php echo htmlspecialchars($selected_date); ?></strong> is:</p>
                    <h3><span class="badge bg-success"><?php echo htmlspecialchars(sprintf('%03d', $next_serial)); ?></span></h3>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
