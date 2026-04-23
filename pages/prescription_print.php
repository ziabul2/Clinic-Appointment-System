<?php
$page_title = "Print Prescription";
require_once '../includes/header.php';

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$prescription_html = '';

if ($appointment_id) {
    $prescDir = __DIR__ . '/../prescriptions';
    if (is_dir($prescDir)) {
        $files = glob($prescDir . "/prescription_{$appointment_id}_*.html");
        if ($files) {
            usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
            $prescription_html = file_get_contents($files[0]);
        }
    }
}

if (empty($prescription_html)) {
    echo '<div class="alert alert-warning">No prescription found for this appointment.</div>';
    require_once '../includes/footer.php';
    exit;
}

// Simple print-friendly wrapper
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Prescription</h2>
        <div>
            <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <?php echo $prescription_html; ?>
        </div>
    </div>
    <p class="text-muted mt-2">Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
</div>

<?php require_once '../includes/footer.php'; ?>
