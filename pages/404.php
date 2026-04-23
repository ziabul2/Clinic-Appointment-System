<?php
http_response_code(404);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-5 text-center">
    <h1 class="display-4">404 — Not Found</h1>
    <p class="lead">The page or resource you requested does not exist.</p>
    <p>If you followed a broken link, please check the URL or return to the dashboard.</p>
    <a class="btn btn-primary" href="/clinicapp/index.php">Go to Home</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
