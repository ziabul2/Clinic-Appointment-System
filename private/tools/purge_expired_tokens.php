<?php
/**
 * Admin tool to purge expired password reset tokens.
 * Can be run via web (admin only) or CLI.
 */
require_once __DIR__ . '/../../config/config.php';

// Allow CLI execution
if (php_sapi_name() === 'cli') {
    echo "Purging expired password reset tokens...\n";
    try {
        $stmt = $db->prepare('DELETE FROM password_reset_tokens WHERE expires_at < NOW()');
        $stmt->execute();
        $cnt = $stmt->rowCount();
        echo "Deleted $cnt expired tokens.\n";
        exit(0);
    } catch (Exception $e) {
        echo "Failed: " . $e->getMessage() . "\n";
        exit(2);
    }
}

// Web execution: require logged-in admin
if (!isLoggedIn()) {
    http_response_code(403);
    echo "Unauthorized\n"; exit;
}
$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'root'])) {
    http_response_code(403);
    echo "Forbidden\n"; exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $_SESSION['error'] = 'Invalid CSRF token.'; redirect('../pages/tools.php');
    }
    try {
        $stmt = $db->prepare('DELETE FROM password_reset_tokens WHERE expires_at < NOW()');
        $stmt->execute();
        $cnt = $stmt->rowCount();
        $_SESSION['success'] = "Purged $cnt expired tokens.";
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to purge expired tokens: ' . $e->getMessage();
    }
    redirect('../pages/tools.php');
}

// Show a small confirmation form
$page_title = 'Purge Expired Tokens';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="pt-3">
    <h1 class="h3">Purge Expired Password Reset Tokens</h1>
    <p>This will permanently remove tokens whose <code>expires_at</code> is before the current time.</p>
    <form method="post">
        <?php echo csrf_input(); ?>
        <button class="btn btn-danger">Purge Now</button>
        <a class="btn btn-secondary" href="../pages/tools.php">Cancel</a>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php';
