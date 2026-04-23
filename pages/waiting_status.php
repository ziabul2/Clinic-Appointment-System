<?php
$page_title = 'Waiting Status';
require_once '../includes/header.php';

$waiting_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['waiting_id']) ? intval($_SESSION['waiting_id']) : 0);
$token = isset($_GET['token']) ? $_GET['token'] : null;

if (!$waiting_id) {
    $_SESSION['error'] = 'No waiting record specified.';
    redirect('pages/register.php');
}

try {
    $q = $db->prepare('SELECT w.*, p.first_name, p.last_name, p.email FROM waiting_list w JOIN patients p ON w.patient_id = p.patient_id WHERE w.waiting_id = :id LIMIT 1');
    $q->bindParam(':id', $waiting_id);
    $q->execute();
    if ($q->rowCount() == 0) {
        $_SESSION['error'] = 'Waiting record not found.';
        redirect('pages/register.php');
    }
    $row = $q->fetch(PDO::FETCH_ASSOC);

    // Verify token if present
    if (!empty($row['token']) && !empty($token) && !hash_equals($row['token'], $token)) {
        $_SESSION['error'] = 'Invalid waiting token.';
        redirect('pages/register.php');
    }

    // Calculate position: count waiting entries earlier than this one
    $posQ = $db->prepare("SELECT COUNT(*) as cnt FROM waiting_list WHERE status = 'waiting' AND requested_at < :requested_at");
    $posQ->bindParam(':requested_at', $row['requested_at']);
    $posQ->execute(); $pRow = $posQ->fetch(PDO::FETCH_ASSOC);
    $position = intval($pRow['cnt']) + 1;

    // Active waiting count and today's registrations
    $activeQ = $db->query("SELECT COUNT(*) as cnt FROM waiting_list WHERE status = 'waiting'"); $active = intval($activeQ->fetch(PDO::FETCH_ASSOC)['cnt']);
    $todayQ = $db->query("SELECT COUNT(*) as cnt FROM patients WHERE DATE(created_at) = CURDATE()"); $today = intval($todayQ->fetch(PDO::FETCH_ASSOC)['cnt']);

} catch (Exception $e) {
    logAction('WAITING_STATUS_ERROR', $e->getMessage());
    $_SESSION['error'] = 'Unable to retrieve waiting status.';
    redirect('pages/register.php');
}

?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h4>Waiting Status</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($row['email'])): ?><p>Notification sent to: <strong><?php echo htmlspecialchars($row['email']); ?></strong></p><?php endif; ?>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>
                <p><strong>Your position in queue:</strong> <?php echo $position; ?></p>
                <p><strong>Active waiting now:</strong> <?php echo $active; ?></p>
                <p><strong>Today's registrations:</strong> <?php echo $today; ?></p>

                <?php if ($row['status'] === 'waiting'): ?>
                    <div class="alert alert-info">Please wait. A receptionist will process your request shortly.</div>
                <?php else: ?>
                    <div class="alert alert-success">Status updated: <?php echo htmlspecialchars($row['status']); ?></div>
                <?php endif; ?>

                <div class="mt-3">
                    <a href="../index.php" class="btn btn-secondary">Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
