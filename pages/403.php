<?php
http_response_code(403);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-5 text-center">
    <h1 class="display-4">403 — Access Denied</h1>
    <p class="lead">You do not have permission to access this resource.</p>
    <p>If you believe this is an error, please contact the administrator.</p>
    <a class="btn btn-secondary" href="/clinicapp/index.php">Back to Home</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
